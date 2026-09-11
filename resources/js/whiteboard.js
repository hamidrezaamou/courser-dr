/**
 * Whiteboard — handwriting canvas inside the patient file.
 *
 * Strokes are kept as vectors rather than pixels. That is what makes undo
 * instant, keeps handwriting sharp when the panel resizes or goes fullscreen,
 * and lets the live stroke and the committed stroke go through the exact same
 * renderer, so nothing shifts or changes weight when the pen is lifted.
 *
 * Two stacked canvases:
 *   base — committed ink, repainted only when the stroke list changes
 *   live — the stroke currently under the pen, cleared every frame
 */
import { getStroke } from 'perfect-freehand';

const MAX_HISTORY = 80;

/** Points closer than this (in CSS px) add nothing but work. */
const MIN_POINT_DISTANCE = 0.4;

/** Ignore touch for this long after the pen lifts, so a resting palm is quiet. */
const PEN_LOCKOUT_MS = 400;

/** Above this the browser gains nothing and memory suffers. */
const MAX_DPR = 2.5;

function penOptions(size, isPen) {
  return {
    size,
    thinning: isPen ? 0.5 : 0.36,
    smoothing: 0.62,
    streamline: isPen ? 0.42 : 0.5,
    easing: (t) => Math.sin((t * Math.PI) / 2),
    simulatePressure: !isPen,
    last: true,
  };
}

/**
 * perfect-freehand returns a polygon; rounding its corners through the
 * midpoints is what removes the faceted look on thick strokes.
 */
function outlinePath(points, options) {
  const outline = getStroke(points, options);
  if (!outline || outline.length < 3) {
    return null;
  }

  const path = new Path2D();
  path.moveTo(outline[0][0], outline[0][1]);

  for (let i = 0; i < outline.length; i++) {
    const a = outline[i];
    const b = outline[(i + 1) % outline.length];
    path.quadraticCurveTo(a[0], a[1], (a[0] + b[0]) / 2, (a[1] + b[1]) / 2);
  }

  path.closePath();

  return path;
}

function pressureOf(event) {
  const p = event.pressure;

  // Chrome reports 0 on the very first pen sample and 0.5 for mouse buttons.
  if (typeof p === 'number' && p > 0 && p < 1) {
    return p;
  }
  if (p === 1 && event.pointerType === 'pen') {
    return 1;
  }

  return event.pointerType === 'pen' ? 0.45 : 0.5;
}

class Whiteboard {
  constructor(canvas) {
    this.baseCanvas = canvas;
    this.stage = canvas.closest('.tg-whiteboard-stage') || canvas.parentElement;
    this.panel = canvas.closest('.tg-whiteboard-panel');

    this.baseCtx = canvas.getContext('2d');

    this.liveCanvas = document.createElement('canvas');
    this.liveCanvas.className = 'tg-whiteboard-live';
    this.liveCanvas.setAttribute('aria-hidden', 'true');
    this.stage.appendChild(this.liveCanvas);
    // Low latency is safe here because this layer is fully repainted each frame.
    this.liveCtx =
      this.liveCanvas.getContext('2d', { desynchronized: true }) ||
      this.liveCanvas.getContext('2d');

    this.cursor = document.createElement('div');
    this.cursor.className = 'tg-whiteboard-cursor';
    this.stage.appendChild(this.cursor);

    this.tool = 'pen';
    this.color = '#2e3f50';
    this.baseSize = 3;

    this.strokes = [];
    this.undoStack = [];
    this.redoStack = [];
    this.background = null;
    this.dirty = false;
    this.saving = false;

    this.current = null;
    this.pointerId = null;
    this.pointerType = null;
    this.rect = null;
    this.penActive = false;
    this.penLiftedAt = 0;
    this._erasedUpTo = 0;
    this._frame = 0;
    this._resizeTimer = 0;

    this.cssW = 0;
    this.cssH = 0;
    this.dpr = 1;

    this.bindUi();
    this.bindPointer();
    this.observeResize();
    this.syncSize();
  }

  // ---------------------------------------------------------------- sizing

  penWidth() {
    return Math.max(1.5, this.baseSize * 2.2);
  }

  eraserWidth() {
    return Math.max(14, this.baseSize * 3.2);
  }

  /**
   * Measure the stage and rebuild the backing stores. Returns false while the
   * panel is hidden, because a zero-sized measurement would throw the drawing
   * away.
   */
  syncSize() {
    if (!this.stage) {
      return false;
    }

    const rect = this.stage.getBoundingClientRect();
    const cssW = Math.floor(rect.width);
    const cssH = Math.floor(rect.height);

    if (cssW < 40 || cssH < 40) {
      return false;
    }

    const dpr = Math.min(window.devicePixelRatio || 1, MAX_DPR);
    if (cssW === this.cssW && cssH === this.cssH && dpr === this.dpr) {
      return true;
    }

    // Keep the drawing where the user put it, proportionally.
    if (this.cssW > 0 && this.cssH > 0) {
      const sx = cssW / this.cssW;
      const sy = cssH / this.cssH;
      const scale = (sx + sy) / 2;

      for (const stroke of this.strokes) {
        for (const point of stroke.points) {
          point[0] *= sx;
          point[1] *= sy;
        }
        stroke.size *= scale;
      }
    }

    this.cssW = cssW;
    this.cssH = cssH;
    this.dpr = dpr;

    for (const layer of [this.baseCanvas, this.liveCanvas]) {
      layer.width = Math.round(cssW * dpr);
      layer.height = Math.round(cssH * dpr);
    }

    this.baseCtx.setTransform(dpr, 0, 0, dpr, 0, 0);
    this.liveCtx.setTransform(dpr, 0, 0, dpr, 0, 0);

    this.rect = this.baseCanvas.getBoundingClientRect();
    this.renderBase();

    return true;
  }

  /** The panel animates open, so the first measurement can still be empty. */
  syncSizeSoon(attempts = 12) {
    if (this.syncSize() || attempts <= 0) {
      return;
    }
    requestAnimationFrame(() => this.syncSizeSoon(attempts - 1));
  }

  observeResize() {
    if (typeof ResizeObserver === 'undefined') {
      window.addEventListener('resize', () => this.scheduleResize());
      return;
    }

    this._observer = new ResizeObserver(() => this.scheduleResize());
    this._observer.observe(this.stage);
  }

  scheduleResize() {
    clearTimeout(this._resizeTimer);
    this._resizeTimer = setTimeout(() => this.syncSize(), 80);
  }

  // ------------------------------------------------------------- rendering

  drawStroke(ctx, stroke) {
    if (stroke.tool === 'eraser') {
      this.drawEraser(ctx, stroke.points, stroke.size, 0);
      return;
    }

    const path = outlinePath(stroke.points, penOptions(stroke.size, stroke.isPen));
    if (!path) {
      return;
    }

    ctx.save();
    ctx.globalCompositeOperation = 'source-over';
    ctx.fillStyle = stroke.color;
    ctx.fill(path);
    ctx.restore();
  }

  /**
   * The eraser removes pixels instead of painting white, so it also works on a
   * drawing loaded from an earlier visit. `from` lets an in-progress erase
   * extend only the part that is new since the previous frame.
   */
  drawEraser(ctx, points, size, from) {
    if (points.length === 0) {
      return;
    }

    ctx.save();
    ctx.globalCompositeOperation = 'destination-out';
    ctx.strokeStyle = '#000';
    ctx.fillStyle = '#000';
    ctx.lineWidth = size;
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';

    const start = Math.max(0, Math.min(from, points.length - 1));

    if (points.length === 1 || start >= points.length - 1) {
      const last = points[points.length - 1];
      ctx.beginPath();
      ctx.arc(last[0], last[1], size / 2, 0, Math.PI * 2);
      ctx.fill();
    } else {
      ctx.beginPath();
      ctx.moveTo(points[start][0], points[start][1]);
      for (let i = start + 1; i < points.length; i++) {
        ctx.lineTo(points[i][0], points[i][1]);
      }
      ctx.stroke();
    }

    ctx.restore();
  }

  clearLayer(ctx, canvas) {
    ctx.save();
    ctx.setTransform(1, 0, 0, 1, 0, 0);
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    ctx.restore();
  }

  renderBase() {
    if (!this.cssW) {
      return;
    }

    this.clearLayer(this.baseCtx, this.baseCanvas);

    if (this.background) {
      this.baseCtx.save();
      this.baseCtx.globalCompositeOperation = 'source-over';
      this.baseCtx.drawImage(this.background, 0, 0, this.cssW, this.cssH);
      this.baseCtx.restore();
    }

    for (const stroke of this.strokes) {
      this.drawStroke(this.baseCtx, stroke);
    }
  }

  renderLive() {
    this.clearLayer(this.liveCtx, this.liveCanvas);

    if (this.current && this.current.tool !== 'eraser') {
      this.drawStroke(this.liveCtx, this.current);
    }
  }

  scheduleFrame() {
    if (this._frame) {
      return;
    }

    this._frame = requestAnimationFrame(() => {
      this._frame = 0;
      this.flush();
    });
  }

  flush() {
    if (!this.current) {
      this.renderLive();
      return;
    }

    if (this.current.tool === 'eraser') {
      // Erase straight onto the base layer so ink disappears under the tip.
      this.drawEraser(
        this.baseCtx,
        this.current.points,
        this.current.size,
        Math.max(0, this._erasedUpTo - 1)
      );
      this._erasedUpTo = this.current.points.length;
      return;
    }

    this.renderLive();
  }

  // --------------------------------------------------------------- history

  pushHistory() {
    this.undoStack.push({ strokes: this.strokes.slice(), background: this.background });
    if (this.undoStack.length > MAX_HISTORY) {
      this.undoStack.shift();
    }
    this.redoStack.length = 0;
  }

  undo() {
    if (this.undoStack.length === 0) {
      return;
    }

    this.redoStack.push({ strokes: this.strokes.slice(), background: this.background });

    const state = this.undoStack.pop();
    this.strokes = state.strokes;
    this.background = state.background;

    this.renderBase();
    this.dirty = true;
    this.updateHistoryButtons();
  }

  redo() {
    if (this.redoStack.length === 0) {
      return;
    }

    this.undoStack.push({ strokes: this.strokes.slice(), background: this.background });

    const state = this.redoStack.pop();
    this.strokes = state.strokes;
    this.background = state.background;

    this.renderBase();
    this.dirty = true;
    this.updateHistoryButtons();
  }

  updateHistoryButtons() {
    if (this.undoBtn) {
      this.undoBtn.disabled = this.undoStack.length === 0;
    }
    if (this.redoBtn) {
      this.redoBtn.disabled = this.redoStack.length === 0;
    }
  }

  // --------------------------------------------------------------- pointer

  bindPointer() {
    const canvas = this.baseCanvas;

    canvas.addEventListener('pointerdown', (e) => this.onDown(e), { passive: false });
    canvas.addEventListener('pointermove', (e) => this.onMove(e), { passive: false });
    canvas.addEventListener('pointerup', (e) => this.onUp(e));
    canvas.addEventListener('pointercancel', (e) => this.onCancel(e));
    canvas.addEventListener('contextmenu', (e) => e.preventDefault());
    canvas.addEventListener('pointerenter', () => {
      this.rect = canvas.getBoundingClientRect();
      this.showCursor(true);
    });
    canvas.addEventListener('pointerleave', () => this.showCursor(false));

    // Deliberately no pointerleave -> end stroke: pointer capture is what keeps
    // a stroke alive when the pen strays past the edge mid-word.
  }

  ignoresTouch(event) {
    if (event.pointerType !== 'touch') {
      return false;
    }
    if (this.penActive) {
      return true;
    }

    return performance.now() - this.penLiftedAt < PEN_LOCKOUT_MS;
  }

  onDown(event) {
    // Only the primary button draws; the barrel button is handled below.
    if (event.pointerType === 'mouse' && event.button !== 0) {
      return;
    }

    if (event.pointerType === 'pen') {
      this.penActive = true;
      // A palm usually lands just before the nib does.
      if (this.current && this.pointerType === 'touch') {
        this.cancelStroke();
      }
    }

    if (this.ignoresTouch(event)) {
      return;
    }

    if (this.current) {
      // A second finger means a gesture, not a line — drop the accidental mark.
      if (event.pointerType === 'touch' && this.pointerType === 'touch') {
        this.cancelStroke();
      }
      return;
    }

    if (!this.syncSize() && !this.cssW) {
      return;
    }

    event.preventDefault();

    this.rect = this.baseCanvas.getBoundingClientRect();
    this.pointerId = event.pointerId;
    this.pointerType = event.pointerType;

    const isPen = event.pointerType === 'pen';
    const barrelErase = isPen && (event.buttons & 32) !== 0;
    const erasing = this.tool === 'eraser' || barrelErase;

    this.current = {
      tool: erasing ? 'eraser' : 'pen',
      color: this.color,
      size: erasing ? this.eraserWidth() : this.penWidth(),
      isPen,
      points: [this.pointOf(event)],
    };
    this._erasedUpTo = 0;

    try {
      this.baseCanvas.setPointerCapture(event.pointerId);
    } catch {
      /* capture is a nicety, not a requirement */
    }

    this.scheduleFrame();
  }

  onMove(event) {
    if (this.tool === 'eraser') {
      this.moveCursor(event);
    }

    if (!this.current || event.pointerId !== this.pointerId) {
      return;
    }

    event.preventDefault();

    // Coalesced events already carry every raw sample the device produced.
    const batch = event.getCoalescedEvents ? event.getCoalescedEvents() : [event];
    for (const sample of batch) {
      this.addPoint(sample);
    }

    this.scheduleFrame();
  }

  onUp(event) {
    if (event.pointerType === 'pen') {
      this.penActive = false;
      this.penLiftedAt = performance.now();
    }

    if (!this.current || event.pointerId !== this.pointerId) {
      return;
    }

    this.addPoint(event);
    this.commit();
    this.release(event);
  }

  onCancel(event) {
    if (event.pointerType === 'pen') {
      this.penActive = false;
      this.penLiftedAt = performance.now();
    }

    if (!this.current || event.pointerId !== this.pointerId) {
      return;
    }

    this.cancelStroke();
    this.release(event);
  }

  release(event) {
    this.pointerId = null;
    this.pointerType = null;

    try {
      this.baseCanvas.releasePointerCapture(event.pointerId);
    } catch {
      /* already released */
    }
  }

  pointOf(event) {
    const rect = this.rect;

    return [
      ((event.clientX - rect.left) / rect.width) * this.cssW,
      ((event.clientY - rect.top) / rect.height) * this.cssH,
      pressureOf(event),
    ];
  }

  addPoint(event) {
    const point = this.pointOf(event);
    const points = this.current.points;
    const previous = points[points.length - 1];

    if (previous) {
      const dx = point[0] - previous[0];
      const dy = point[1] - previous[1];
      if (dx * dx + dy * dy < MIN_POINT_DISTANCE * MIN_POINT_DISTANCE) {
        return;
      }
    }

    points.push(point);
  }

  commit() {
    const stroke = this.current;
    this.current = null;

    if (this._frame) {
      cancelAnimationFrame(this._frame);
      this._frame = 0;
    }

    this.clearLayer(this.liveCtx, this.liveCanvas);

    if (!stroke || stroke.points.length === 0) {
      return;
    }

    this.pushHistory();

    if (stroke.tool === 'eraser') {
      this.drawEraser(
        this.baseCtx,
        stroke.points,
        stroke.size,
        Math.max(0, this._erasedUpTo - 1)
      );
    } else {
      this.drawStroke(this.baseCtx, stroke);
    }

    this.strokes.push(stroke);
    this.dirty = true;
    this.updateHistoryButtons();
  }

  cancelStroke() {
    const wasErasing = this.current && this.current.tool === 'eraser';
    this.current = null;

    if (this._frame) {
      cancelAnimationFrame(this._frame);
      this._frame = 0;
    }

    this.clearLayer(this.liveCtx, this.liveCanvas);

    // The eraser already bit into the base layer, so rebuild it.
    if (wasErasing) {
      this.renderBase();
    }
  }

  // ---------------------------------------------------------- eraser ring

  showCursor(visible) {
    this.cursor.classList.toggle('is-visible', visible && this.tool === 'eraser');
  }

  moveCursor(event) {
    const rect = this.rect || this.baseCanvas.getBoundingClientRect();
    const size = this.eraserWidth() * (this.cssW ? rect.width / this.cssW : 1);

    this.cursor.style.width = `${size}px`;
    this.cursor.style.height = `${size}px`;
    this.cursor.style.transform =
      `translate(${event.clientX - rect.left - size / 2}px, ${event.clientY - rect.top - size / 2}px)`;
    this.cursor.classList.add('is-visible');
  }

  // -------------------------------------------------------------------- UI

  bindUi() {
    const root = this.panel || document;

    root.querySelectorAll('.canvas-tool-btn').forEach((button) => {
      button.addEventListener('click', () => {
        this.tool = button.getAttribute('data-tool') || 'pen';
        root.querySelectorAll('.canvas-tool-btn').forEach((other) => {
          other.classList.toggle('is-active', other === button);
        });
        this.stage.classList.toggle('is-erasing', this.tool === 'eraser');
        this.showCursor(false);
      });
    });

    const sizeInput = root.querySelector('#pen-size');
    const sizeLabel = root.querySelector('#pen-size-label');
    if (sizeInput) {
      this.baseSize = Number(sizeInput.value) || 3;
      sizeInput.addEventListener('input', () => {
        this.baseSize = Number(sizeInput.value) || 3;
        if (sizeLabel) {
          sizeLabel.textContent = String(this.baseSize);
        }
      });
    }

    const colorInput = root.querySelector('#pen-color');
    if (colorInput) {
      this.color = colorInput.value || '#2e3f50';
      colorInput.addEventListener('input', () => {
        this.color = colorInput.value || '#2e3f50';
      });
    }

    this.undoBtn = root.querySelector('#undo-canvas-btn');
    this.redoBtn = root.querySelector('#redo-canvas-btn');
    if (this.undoBtn) {
      this.undoBtn.addEventListener('click', () => this.undo());
    }
    if (this.redoBtn) {
      this.redoBtn.addEventListener('click', () => this.redo());
    }
    this.updateHistoryButtons();

    const clearBtn = root.querySelector('#clear-canvas-btn');
    if (clearBtn) {
      clearBtn.addEventListener('click', () => {
        if (this.strokes.length === 0 && !this.background) {
          return;
        }
        if (!confirm('آیا می‌خواهید تمام نوشتار را پاک کنید؟')) {
          return;
        }
        this.pushHistory();
        this.strokes = [];
        this.background = null;
        this.renderBase();
        this.dirty = true;
        this.updateHistoryButtons();
      });
    }

    const saveBtn = root.querySelector('#save-canvas-btn');
    if (saveBtn) {
      saveBtn.addEventListener('click', () => this.save(saveBtn));
    }

    this.bindKeyboard();
    this.bindFullscreen();

    window.addEventListener('whiteboard-load', (event) => {
      this.load(event.detail?.src || null);
    });

    window.addEventListener('beforeunload', (event) => {
      if (!this.dirty || this.saving) {
        return;
      }
      event.preventDefault();
      event.returnValue = '';
    });
  }

  bindKeyboard() {
    window.addEventListener('keydown', (event) => {
      // getClientRects is empty for display:none but works for fixed panels.
      if (!this.panel || this.panel.getClientRects().length === 0) {
        return;
      }

      const key = event.key.toLowerCase();
      if (!(event.ctrlKey || event.metaKey) || (key !== 'z' && key !== 'y')) {
        return;
      }

      event.preventDefault();
      if (key === 'y' || event.shiftKey) {
        this.redo();
      } else {
        this.undo();
      }
    });
  }

  bindFullscreen() {
    const button = (this.panel || document).querySelector('#whiteboard-fullscreen-btn');
    if (!button || !this.panel) {
      return;
    }

    button.addEventListener('click', async () => {
      try {
        if (document.fullscreenElement === this.panel) {
          await document.exitFullscreen();
        } else if (this.panel.requestFullscreen) {
          await this.panel.requestFullscreen();
        } else {
          this.panel.classList.toggle('is-fullscreen');
        }
      } catch {
        this.panel.classList.toggle('is-fullscreen');
      }
      this.syncSizeSoon();
    });

    document.addEventListener('fullscreenchange', () => {
      if (!document.fullscreenElement) {
        this.panel.classList.remove('is-fullscreen');
      }
      this.syncSizeSoon();
    });
  }

  load(src) {
    this.strokes = [];
    this.undoStack = [];
    this.redoStack = [];
    this.background = null;
    this.dirty = false;
    this.updateHistoryButtons();

    if (!src) {
      this.renderBase();
      return;
    }

    const image = new Image();
    image.crossOrigin = 'anonymous';
    image.onload = () => {
      this.background = image;
      this.syncSizeSoon();
      this.renderBase();
    };
    image.onerror = () => {
      this.renderBase();
    };
    image.src = src;
  }

  // ------------------------------------------------------------------ save

  /** Flatten everything onto an opaque white PNG at print-friendly density. */
  export() {
    const scale = Math.max(2, this.dpr);
    const out = document.createElement('canvas');
    out.width = Math.round(this.cssW * scale);
    out.height = Math.round(this.cssH * scale);

    const ctx = out.getContext('2d');
    ctx.setTransform(scale, 0, 0, scale, 0, 0);

    if (this.background) {
      ctx.drawImage(this.background, 0, 0, this.cssW, this.cssH);
    }
    for (const stroke of this.strokes) {
      this.drawStroke(ctx, stroke);
    }

    // Slide white underneath so erased areas are white, not transparent.
    ctx.setTransform(1, 0, 0, 1, 0, 0);
    ctx.globalCompositeOperation = 'destination-over';
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, out.width, out.height);

    return out.toDataURL('image/png');
  }

  async save(saveBtn) {
    if (this.saving) {
      return;
    }

    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const patientId = this.baseCanvas.dataset.patientId;
    const storeUrl = this.baseCanvas.dataset.storeUrl;
    if (!patientId || !storeUrl || !this.cssW) {
      return;
    }

    if (this.current) {
      this.commit();
    }

    let visitId = null;
    const root = document.querySelector('.tg-layout');
    if (root?._x_dataStack?.[0]) {
      visitId = root._x_dataStack[0].editingVisitId || null;
    }

    const image = this.export();

    this.saving = true;
    const previousText = saveBtn.textContent;
    saveBtn.disabled = true;
    saveBtn.textContent = 'در حال ذخیره...';

    try {
      const response = await fetch(storeUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-CSRF-TOKEN': csrf,
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({
          patient_id: Number(patientId),
          image,
          visit_id: visitId,
        }),
      });

      if (!response.ok) {
        throw new Error('خطا در ذخیره نقاشی');
      }

      this.dirty = false;
      window.location.reload();
    } catch (error) {
      alert(error.message || 'خطا در ذخیره نقاشی');
      this.saving = false;
      saveBtn.disabled = false;
      saveBtn.textContent = previousText;
    }
  }
}

export function initWhiteboard() {
  const canvas = document.getElementById('exam-canvas');
  if (!canvas) {
    return null;
  }
  if (canvas.dataset.wbReady === '1') {
    return canvas._wbEngine || null;
  }

  canvas.dataset.wbReady = '1';
  canvas._wbEngine = new Whiteboard(canvas);

  return canvas._wbEngine;
}

document.addEventListener('DOMContentLoaded', initWhiteboard);

window.addEventListener('whiteboard-open', () => {
  const engine = initWhiteboard();
  if (engine) {
    engine.syncSizeSoon();
  }
});
