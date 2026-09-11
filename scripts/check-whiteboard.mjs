/**
 * Drives resources/js/whiteboard.js against a minimal fake DOM so the drawing
 * engine can be exercised without a browser.
 *
 * These assertions exist because the previous engine shipped bugs that were
 * invisible in code review: duplicated pointer samples, a live stroke drawn at
 * half the committed weight, and an eraser that removed far more than it drew.
 *
 * Run: node scripts/check-whiteboard.mjs
 */

let failures = 0;
let currentTest = '';

function check(label, ok, detail = '') {
  const status = ok ? 'OK' : 'FAIL';
  console.log(`  ${status.padEnd(5)} ${label}${detail ? '  — ' + detail : ''}`);
  if (!ok) failures++;
}

function test(name, fn) {
  currentTest = name;
  console.log(`\n${name}`);
  try {
    fn();
  } catch (error) {
    console.log(`  FAIL  threw: ${error.message}`);
    console.log(error.stack.split('\n').slice(1, 4).join('\n'));
    failures++;
  }
}

// ---------------------------------------------------------------- fake DOM

let now = 1000;
let rafQueue = [];
let rafId = 0;

class FakePath2D {
  constructor() {
    this.cmds = [];
  }
  moveTo(x, y) { this.cmds.push(['M', x, y]); }
  lineTo(x, y) { this.cmds.push(['L', x, y]); }
  quadraticCurveTo(a, b, c, d) { this.cmds.push(['Q', a, b, c, d]); }
  arc(x, y, r) { this.cmds.push(['A', x, y, r]); }
  closePath() { this.cmds.push(['Z']); }
}

class FakeCtx {
  constructor(canvas) {
    this.canvas = canvas;
    this.ops = [];
    this.globalCompositeOperation = 'source-over';
    this.fillStyle = '#000';
    this.strokeStyle = '#000';
    this.lineWidth = 1;
    this.lineCap = 'butt';
    this.lineJoin = 'miter';
    this._stack = [];
    this._path = null;
  }
  save() {
    this._stack.push({
      gco: this.globalCompositeOperation,
      fill: this.fillStyle,
      stroke: this.strokeStyle,
      lw: this.lineWidth,
    });
  }
  restore() {
    const s = this._stack.pop();
    if (!s) return;
    this.globalCompositeOperation = s.gco;
    this.fillStyle = s.fill;
    this.strokeStyle = s.stroke;
    this.lineWidth = s.lw;
  }
  setTransform(a, b, c, d, e, f) { this.ops.push({ op: 'setTransform', a, d, e, f }); }
  clearRect(x, y, w, h) { this.ops.push({ op: 'clearRect', x, y, w, h }); }
  fillRect(x, y, w, h) {
    this.ops.push({ op: 'fillRect', x, y, w, h, fillStyle: this.fillStyle, gco: this.globalCompositeOperation });
  }
  drawImage(img, x, y, w, h) { this.ops.push({ op: 'drawImage', img, x, y, w, h }); }
  beginPath() { this._path = new FakePath2D(); }
  moveTo(x, y) { this._path?.moveTo(x, y); }
  lineTo(x, y) { this._path?.lineTo(x, y); }
  arc(x, y, r) { this._path?.arc(x, y, r); }
  stroke() {
    this.ops.push({
      op: 'stroke', path: this._path, lineWidth: this.lineWidth,
      gco: this.globalCompositeOperation, lineCap: this.lineCap,
    });
  }
  fill(path) {
    this.ops.push({
      op: 'fill', path: path || this._path,
      fillStyle: this.fillStyle, gco: this.globalCompositeOperation,
    });
  }
  get fills() { return this.ops.filter((o) => o.op === 'fill'); }
  get strokes() { return this.ops.filter((o) => o.op === 'stroke'); }
  reset() { this.ops = []; }
}

class El {
  constructor(tag, opts = {}) {
    this.tagName = tag.toUpperCase();
    this.id = opts.id || '';
    this.children = [];
    this.parent = null;
    this.listeners = {};
    this.style = {};
    this.dataset = opts.dataset || {};
    this.disabled = false;
    this.value = opts.value;
    this.textContent = '';
    this._attrs = opts.attrs || {};
    this._rect = opts.rect || { left: 0, top: 0, width: 0, height: 0 };
    this._classes = new Set(String(opts.className || '').split(' ').filter(Boolean));

    this.classList = {
      add: (c) => this._classes.add(c),
      remove: (c) => this._classes.delete(c),
      contains: (c) => this._classes.has(c),
      toggle: (c, force) => {
        const on = force === undefined ? !this._classes.has(c) : !!force;
        if (on) this._classes.add(c); else this._classes.delete(c);
        return on;
      },
    };
  }

  get className() { return [...this._classes].join(' '); }
  set className(v) { this._classes = new Set(String(v).split(' ').filter(Boolean)); }

  setAttribute(name, value) { this._attrs[name] = value; }
  getAttribute(name) { return this._attrs[name] ?? null; }

  addEventListener(type, fn) { (this.listeners[type] ||= []).push(fn); }
  removeEventListener() {}
  emit(type, event = {}) {
    for (const fn of this.listeners[type] || []) fn(event);
  }

  appendChild(child) { child.parent = this; this.children.push(child); return child; }
  setPointerCapture() {}
  releasePointerCapture() {}

  setRect(rect) { this._rect = rect; }
  getBoundingClientRect() {
    const r = this._rect;
    return { ...r, right: r.left + r.width, bottom: r.top + r.height };
  }
  getClientRects() { return this._rect.width > 0 ? [{}] : []; }

  matches(selector) {
    if (selector.startsWith('#')) return this.id === selector.slice(1);
    if (selector.startsWith('.')) return this._classes.has(selector.slice(1));
    return this.tagName === selector.toUpperCase();
  }

  closest(selector) {
    let node = this;
    while (node) {
      if (node.matches(selector)) return node;
      node = node.parent;
    }
    return null;
  }

  descendants() {
    const out = [];
    const walk = (node) => {
      for (const child of node.children) { out.push(child); walk(child); }
    };
    walk(this);
    return out;
  }

  querySelector(selector) {
    return this.descendants().find((el) => el.matches(selector)) || null;
  }
  querySelectorAll(selector) {
    const list = this.descendants().filter((el) => el.matches(selector));
    list.forEach = Array.prototype.forEach.bind(list);
    return list;
  }
}

class Canvas extends El {
  constructor(opts = {}) {
    super('canvas', opts);
    this.width = 300;
    this.height = 150;
    this._ctx = null;
  }
  getContext() {
    this._ctx ||= new FakeCtx(this);
    return this._ctx;
  }
  toDataURL() { return 'data:image/png;base64,FAKE'; }
}

/** Builds the whiteboard panel subtree the way show.blade.php does. */
function buildDom({ stageRect } = {}) {
  const panel = new El('div', { id: 'whiteboard-panel', className: 'tg-whiteboard-panel', rect: { left: 0, top: 0, width: 900, height: 700 } });

  const toolbar = new El('div', { id: 'canvas-toolbar' });
  const penBtn = new El('button', { className: 'pp-tool canvas-tool-btn is-active', attrs: { 'data-tool': 'pen' } });
  const eraserBtn = new El('button', { className: 'pp-tool canvas-tool-btn', attrs: { 'data-tool': 'eraser' } });
  const undoBtn = new El('button', { id: 'undo-canvas-btn' });
  const redoBtn = new El('button', { id: 'redo-canvas-btn' });
  const sizeInput = new El('input', { id: 'pen-size', value: '3' });
  const sizeLabel = new El('span', { id: 'pen-size-label' });
  const colorInput = new El('input', { id: 'pen-color', value: '#2e3f50' });
  [penBtn, eraserBtn, undoBtn, redoBtn, sizeInput, sizeLabel, colorInput].forEach((el) => toolbar.appendChild(el));

  const stage = new El('div', {
    id: 'whiteboard-stage',
    className: 'tg-whiteboard-stage',
    rect: stageRect || { left: 20, top: 40, width: 800, height: 600 },
  });
  const canvas = new Canvas({
    id: 'exam-canvas',
    className: 'pp-canvas tg-whiteboard-canvas',
    dataset: { patientId: '7', storeUrl: '/visits/drawing' },
    rect: stageRect || { left: 20, top: 40, width: 800, height: 600 },
  });
  stage.appendChild(canvas);

  const clearBtn = new El('button', { id: 'clear-canvas-btn' });
  const saveBtn = new El('button', { id: 'save-canvas-btn' });

  panel.appendChild(toolbar);
  panel.appendChild(stage);
  panel.appendChild(clearBtn);
  panel.appendChild(saveBtn);

  return { panel, stage, canvas, penBtn, eraserBtn, undoBtn, redoBtn, sizeInput, colorInput, clearBtn, saveBtn };
}

let dom;

function installGlobals() {
  const documentEl = {
    fullscreenElement: null,
    getElementById: (id) => (dom ? dom.panel.descendants().find((el) => el.id === id) || (dom.panel.id === id ? dom.panel : null) : null),
    querySelector: (sel) => (dom ? (dom.panel.matches(sel) ? dom.panel : dom.panel.querySelector(sel)) : null),
    querySelectorAll: (sel) => (dom ? dom.panel.querySelectorAll(sel) : []),
    createElement: (tag) => {
      if (tag !== 'canvas') return new El(tag);
      globalThis.__lastCanvas = new Canvas();
      return globalThis.__lastCanvas;
    },
    addEventListener: () => {},
  };

  globalThis.document = documentEl;
  globalThis.window = {
    devicePixelRatio: 2,
    addEventListener: (type, fn) => { (globalThis.__winListeners[type] ||= []).push(fn); },
  };
  globalThis.__winListeners = {};
  globalThis.Path2D = FakePath2D;
  globalThis.Image = class { set src(v) { this._src = v; } get src() { return this._src; } };
  globalThis.ResizeObserver = class { observe() {} disconnect() {} };
  globalThis.performance = { now: () => now };
  globalThis.requestAnimationFrame = (fn) => { rafQueue.push([++rafId, fn]); return rafId; };
  globalThis.cancelAnimationFrame = (id) => { rafQueue = rafQueue.filter(([i]) => i !== id); };
  globalThis.confirm = () => true;
  globalThis.alert = () => {};
}

function runFrames(times = 1) {
  for (let i = 0; i < times; i++) {
    const queue = rafQueue;
    rafQueue = [];
    for (const [, fn] of queue) fn();
  }
}

function pointer(type, x, y, extra = {}) {
  return {
    pointerId: extra.pointerId ?? 1,
    pointerType: type,
    clientX: x,
    clientY: y,
    pressure: extra.pressure ?? 0.5,
    buttons: extra.buttons ?? 1,
    button: extra.button ?? 0,
    isPrimary: extra.isPrimary ?? true,
    preventDefault() {},
    getCoalescedEvents: extra.coalesced,
  };
}

installGlobals();
const { initWhiteboard } = await import('../resources/js/whiteboard.js');

function freshEngine(options = {}) {
  rafQueue = [];
  now = 1000;
  dom = buildDom(options);
  const engine = initWhiteboard();
  engine.syncSize();
  return engine;
}

/** Draw a complete stroke; returns the engine. */
function stroke(engine, points, { type = 'pen', pointerId = 1 } = {}) {
  const c = dom.canvas;
  c.emit('pointerdown', pointer(type, points[0][0], points[0][1], { pointerId }));
  for (let i = 1; i < points.length; i++) {
    c.emit('pointermove', pointer(type, points[i][0], points[i][1], { pointerId }));
    runFrames();
  }
  c.emit('pointerup', pointer(type, points[points.length - 1][0], points[points.length - 1][1], { pointerId }));
  return engine;
}

// ------------------------------------------------------------------- tests

test('canvas sizing follows the stage and devicePixelRatio', () => {
  const engine = freshEngine();
  check('logical size matches the stage', engine.cssW === 800 && engine.cssH === 600, `${engine.cssW}x${engine.cssH}`);
  check('backing store is dpr-scaled', dom.canvas.width === 1600 && dom.canvas.height === 1200, `${dom.canvas.width}x${dom.canvas.height}`);
  check('live layer matches the base layer', engine.liveCanvas.width === dom.canvas.width && engine.liveCanvas.height === dom.canvas.height);
  check('live layer was added to the stage', dom.stage.children.some((c) => c === engine.liveCanvas));
  check('hidden stage does not resize', (() => {
    dom.stage.setRect({ left: 0, top: 0, width: 0, height: 0 });
    const ok = engine.syncSize() === false && engine.cssW === 800;
    dom.stage.setRect({ left: 20, top: 40, width: 800, height: 600 });
    return ok;
  })());
});

test('pointer samples are recorded once, in order', () => {
  const engine = freshEngine();
  const c = dom.canvas;

  c.emit('pointerdown', pointer('pen', 20 + 10, 40 + 10));

  // A real browser hands the same samples to pointermove via getCoalescedEvents.
  const samples = [[30, 30], [40, 40], [50, 50]].map(([x, y]) => pointer('pen', 20 + x, 40 + y));
  c.emit('pointermove', pointer('pen', 20 + 50, 40 + 50, { coalesced: () => samples }));

  const pts = engine.current.points;
  check('one point per sample plus the down point', pts.length === 4, `got ${pts.length}`);

  const xs = pts.map((p) => Math.round(p[0]));
  check('strictly increasing, no backtracking', xs.every((x, i) => i === 0 || x > xs[i - 1]), xs.join(','));
  check('coordinates are stage-relative', xs.join(',') === '10,30,40,50', xs.join(','));
});

test('committed stroke is identical to the last live stroke', () => {
  const engine = freshEngine();
  const c = dom.canvas;
  const pts = [[100, 100], [140, 130], [180, 100], [220, 150]];

  c.emit('pointerdown', pointer('pen', 20 + pts[0][0], 40 + pts[0][1]));
  for (let i = 1; i < pts.length; i++) {
    c.emit('pointermove', pointer('pen', 20 + pts[i][0], 40 + pts[i][1]));
  }
  runFrames();

  const liveFill = engine.liveCtx.fills.at(-1);
  check('live stroke was rendered', !!liveFill);

  engine.baseCtx.reset();
  c.emit('pointerup', pointer('pen', 20 + pts.at(-1)[0], 40 + pts.at(-1)[1]));
  const committedFill = engine.baseCtx.fills.at(-1);
  check('stroke was committed', !!committedFill);

  const same = JSON.stringify(liveFill.path.cmds) === JSON.stringify(committedFill.path.cmds);
  check('geometry is byte-identical (no jump on pen up)', same,
    same ? '' : `${liveFill.path.cmds.length} vs ${committedFill.path.cmds.length} cmds`);
  check('same colour', liveFill.fillStyle === committedFill.fillStyle);
});

test('eraser removes pixels at the width it advertises', () => {
  const engine = freshEngine();
  dom.eraserBtn.emit('click');
  check('tool switched', engine.tool === 'eraser');

  const advertised = engine.eraserWidth();
  stroke(engine, [[100, 100], [150, 100], [200, 100]]);

  const erase = engine.baseCtx.strokes.at(-1);
  check('erase happened on the base layer', !!erase);
  check('uses destination-out, not white paint', erase?.gco === 'destination-out', erase?.gco);
  check('committed width equals the ring width', erase?.lineWidth === advertised, `${erase?.lineWidth} vs ${advertised}`);
  check('round cap so the trail has no gaps', erase?.lineCap === 'round');
  check('eraser recorded as an undoable stroke', engine.strokes.length === 1 && engine.strokes[0].tool === 'eraser');
});

test('undo and redo walk the stroke list', () => {
  const engine = freshEngine();
  stroke(engine, [[10, 10], [50, 50], [90, 20]]);
  stroke(engine, [[100, 10], [150, 50], [190, 20]]);
  check('two strokes committed', engine.strokes.length === 2, String(engine.strokes.length));

  engine.undo();
  check('undo removes one', engine.strokes.length === 1, String(engine.strokes.length));
  engine.undo();
  check('undo removes the other', engine.strokes.length === 0);
  check('undo button disabled at the bottom', dom.undoBtn.disabled === true);

  engine.redo();
  check('redo restores one', engine.strokes.length === 1);
  engine.redo();
  check('redo restores the other', engine.strokes.length === 2);
  engine.redo();
  check('redo past the top is a no-op', engine.strokes.length === 2);

  engine.undo();
  stroke(engine, [[300, 300], [340, 340]]);
  check('a new stroke clears the redo branch', engine.redoStack.length === 0 && engine.strokes.length === 2);
});

test('clear is undoable', () => {
  const engine = freshEngine();
  stroke(engine, [[10, 10], [60, 60]]);
  dom.clearBtn.emit('click');
  check('board is empty', engine.strokes.length === 0);
  engine.undo();
  check('undo brings the drawing back', engine.strokes.length === 1);
});

test('palm rejection', () => {
  const engine = freshEngine();
  const c = dom.canvas;

  stroke(engine, [[10, 10], [60, 60]], { type: 'pen' });
  check('pen drew', engine.strokes.length === 1);

  // Palm touches down right after the nib lifts.
  now += 50;
  c.emit('pointerdown', pointer('touch', 200, 200, { pointerId: 9 }));
  check('touch ignored inside the lockout', engine.current === null);

  now += 500;
  c.emit('pointerdown', pointer('touch', 200, 200, { pointerId: 9 }));
  check('touch works again after the lockout', engine.current !== null);
  c.emit('pointerup', pointer('touch', 200, 200, { pointerId: 9 }));

  // Palm lands first, then the nib.
  c.emit('pointerdown', pointer('touch', 300, 300, { pointerId: 11 }));
  check('touch stroke started', engine.current !== null);
  const before = engine.strokes.length;
  c.emit('pointerdown', pointer('pen', 100, 100, { pointerId: 12 }));
  check('pen takes over from the palm', engine.current?.isPen === true);
  check('the palm mark was discarded', engine.strokes.length === before);
});

test('a second finger cancels the stroke instead of scribbling', () => {
  const engine = freshEngine();
  const c = dom.canvas;

  c.emit('pointerdown', pointer('touch', 100, 100, { pointerId: 3 }));
  c.emit('pointermove', pointer('touch', 140, 140, { pointerId: 3 }));
  runFrames();
  check('first finger is drawing', engine.current !== null);

  c.emit('pointerdown', pointer('touch', 300, 300, { pointerId: 4, isPrimary: false }));
  check('stroke cancelled', engine.current === null);
  check('nothing committed', engine.strokes.length === 0);

  c.emit('pointerup', pointer('touch', 140, 140, { pointerId: 3 }));
  check('the lifted finger still commits nothing', engine.strokes.length === 0);
});

test('stroke survives the pointer leaving the canvas', () => {
  const engine = freshEngine();
  const c = dom.canvas;

  c.emit('pointerdown', pointer('pen', 100, 100));
  c.emit('pointermove', pointer('pen', 200, 200));
  c.emit('pointerleave', pointer('pen', 900, 900));
  check('still drawing after leaving', engine.current !== null);

  c.emit('pointermove', pointer('pen', 260, 240));
  c.emit('pointerup', pointer('pen', 260, 240));
  check('committed on pen up', engine.strokes.length === 1);
});

test('resize keeps the drawing proportional', () => {
  const engine = freshEngine();
  stroke(engine, [[100, 100], [200, 200]]);
  const first = engine.strokes[0].points[0].slice();

  dom.stage.setRect({ left: 20, top: 40, width: 400, height: 300 });
  dom.canvas.setRect({ left: 20, top: 40, width: 400, height: 300 });
  engine.syncSize();

  const moved = engine.strokes[0].points[0];
  check('halving the stage halves the coordinates',
    Math.abs(moved[0] - first[0] / 2) < 0.01 && Math.abs(moved[1] - first[1] / 2) < 0.01,
    `${first} -> ${moved}`);
  check('base layer repainted at the new size', engine.cssW === 400);
});

test('export flattens onto opaque white', () => {
  const engine = freshEngine();
  stroke(engine, [[10, 10], [60, 60]]);
  const uri = engine.export();

  check('returns a png data uri', typeof uri === 'string' && uri.startsWith('data:image/png'));

  // The white must go underneath, otherwise erased areas save as transparent.
  const exportCtx = globalThis.__lastCanvas.getContext();
  check('white filled with destination-over',
    exportCtx.ops.some((o) => o.op === 'fillRect' && o.gco === 'destination-over' && o.fillStyle === '#ffffff'));
});

console.log(failures === 0 ? '\nALL PASS' : `\n${failures} FAILURE(S)`);
process.exit(failures === 0 ? 0 : 1);
