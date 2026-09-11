import type { ReactNode } from "react";
import { statusColor } from "../lib/status";

export function ErrorBanner({ message }: { message?: string | null }) {
  if (!message) return null;
  return <div className="banner-error">{message}</div>;
}

export function EmptyState({ text }: { text: string }) {
  return <div className="empty">{text}</div>;
}

export function LoadingBox() {
  return (
    <div className="loading">
      <span className="spinner" />
      در حال خواندن…
    </div>
  );
}

export function StatusChip({ status, label }: { status?: string | null; label?: string | null }) {
  if (!label && !status) return null;
  return (
    <span className="chip" style={{ color: statusColor(status), background: "color-mix(in srgb, currentColor 14%, white)" }}>
      {label || status}
    </span>
  );
}

export function Field({
  label,
  value,
  onChange,
  type = "text",
  autoComplete,
  readOnly,
}: {
  label: string;
  value: string;
  onChange: (value: string) => void;
  type?: string;
  autoComplete?: string;
  readOnly?: boolean;
}) {
  return (
    <label className="field">
      <span>{label}</span>
      <input
        type={type}
        value={value}
        autoComplete={autoComplete}
        readOnly={readOnly}
        onChange={(event) => onChange(event.target.value)}
      />
    </label>
  );
}

export function PrimaryButton({
  children,
  loading,
  disabled,
  onClick,
  type = "button",
}: {
  children: ReactNode;
  loading?: boolean;
  disabled?: boolean;
  onClick?: () => void;
  type?: "button" | "submit";
}) {
  return (
    <button className="btn-primary" type={type} disabled={disabled || loading} onClick={onClick}>
      {loading ? "لطفاً صبر کنید…" : children}
    </button>
  );
}

export function GhostButton({
  children,
  onClick,
  disabled,
}: {
  children: ReactNode;
  onClick?: () => void;
  disabled?: boolean;
}) {
  return (
    <button className="btn-ghost" type="button" disabled={disabled} onClick={onClick}>
      {children}
    </button>
  );
}

export function SelectField({
  label,
  value,
  onChange,
  options,
}: {
  label: string;
  value: string;
  onChange: (value: string) => void;
  options: Array<{ value: string; label: string }>;
}) {
  return (
    <label className="field">
      <span>{label}</span>
      <select value={value} onChange={(event) => onChange(event.target.value)}>
        {options.map((option) => (
          <option key={option.value} value={option.value}>
            {option.label}
          </option>
        ))}
      </select>
    </label>
  );
}

export function ChoiceChip({
  selected,
  onClick,
  children,
}: {
  selected: boolean;
  onClick: () => void;
  children: ReactNode;
}) {
  return (
    <button type="button" className={selected ? "chip-btn on" : "chip-btn"} onClick={onClick}>
      {children}
    </button>
  );
}

export function Card({
  children,
  onClick,
}: {
  children: ReactNode;
  onClick?: () => void;
}) {
  if (onClick) {
    return (
      <button type="button" className="card card-btn" onClick={onClick}>
        {children}
      </button>
    );
  }
  return <div className="card">{children}</div>;
}

export function StatTile({ label, value }: { label: string; value: string | number }) {
  return (
    <div className="stat">
      <strong>{value}</strong>
      <span>{label}</span>
    </div>
  );
}

export function CacheHint({ fromCache, online }: { fromCache: boolean; online: boolean }) {
  if (fromCache) {
    return <p className="hint">{online ? "از کش محلی آمد؛ در حال به‌روزرسانی…" : "نمایش از کش محلی — بدون اتصال به هاست"}</p>;
  }
  return null;
}
