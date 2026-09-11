import type { ReactNode } from "react";
import type { SlotDto } from "../lib/types";
import type { DateOption } from "../lib/bookingDates";

export function FormLabel({ children }: { children: ReactNode }) {
  return <label className="form-label">{children}</label>;
}

export function FieldBlock({
  label,
  children,
  className = "",
}: {
  label: string;
  children: ReactNode;
  className?: string;
}) {
  return (
    <div className={className}>
      <FormLabel>{label}</FormLabel>
      {children}
    </div>
  );
}

export function TextInput({
  value,
  onChange,
  dir,
  inputMode,
  maxLength,
  required,
  readOnly,
  disabled,
  id,
  className = "",
}: {
  value: string;
  onChange?: (value: string) => void;
  dir?: "ltr" | "rtl";
  inputMode?: "numeric" | "tel" | "text";
  maxLength?: number;
  required?: boolean;
  readOnly?: boolean;
  disabled?: boolean;
  id?: string;
  className?: string;
}) {
  return (
    <input
      id={id}
      className={`field-input mt-1 block w-full${dir === "ltr" ? " font-mono" : ""}${readOnly || disabled ? " opacity-80" : ""} ${className}`.trim()}
      dir={dir}
      inputMode={inputMode}
      maxLength={maxLength}
      required={required}
      readOnly={readOnly}
      disabled={disabled}
      value={value}
      onChange={(event) => onChange?.(event.target.value)}
    />
  );
}

export function SelectInput({
  value,
  onChange,
  children,
  required,
  disabled,
}: {
  value: string;
  onChange: (value: string) => void;
  children: ReactNode;
  required?: boolean;
  disabled?: boolean;
}) {
  return (
    <select className="field-input mt-1" value={value} required={required} disabled={disabled} onChange={(event) => onChange(event.target.value)}>
      {children}
    </select>
  );
}

export function DateSelect({
  value,
  onChange,
  options,
  emptyLabel,
}: {
  value: string;
  onChange: (value: string) => void;
  options: DateOption[];
  emptyLabel: string;
}) {
  return (
    <select className="field-input mt-1" value={value} required onChange={(event) => onChange(event.target.value)}>
      <option value="">{options.length ? "-- انتخاب تاریخ --" : emptyLabel}</option>
      {options.map((opt) => (
        <option key={opt.value} value={opt.value} disabled={opt.disabled}>
          {opt.label}
        </option>
      ))}
    </select>
  );
}

export function BookingSlotGrid({
  slots,
  selected,
  loading,
  empty,
  hideBooked,
  onSelect,
  onBooked,
}: {
  slots: SlotDto[];
  selected: string;
  loading?: boolean;
  empty?: string | null;
  hideBooked?: boolean;
  onSelect: (value: string, booked?: boolean, exception?: boolean) => void;
  onBooked?: (slot: SlotDto) => void;
}) {
  const visible = hideBooked ? slots.filter((item) => !item.booked) : slots;
  if (loading) return <div className="booking-time-grid"><div className="booking-slot-loading">بارگذاری...</div></div>;
  if (!visible.length) {
    return (
      <div className="booking-time-grid">
        <div className="booking-slot-empty">{empty || "نوبت آزادی نیست."}</div>
      </div>
    );
  }
  return (
    <div className="booking-time-grid mt-2">
      {visible.map((item) => {
        const value = item.value || "";
        const booked = !!item.booked;
        const classes = [
          "booking-slot",
          booked ? "is-booked" : "",
          booked && onBooked ? "is-booked-clickable" : "",
          item.removed && !booked ? "is-removed" : "",
          item.exception && !booked ? "is-exception-slot" : "",
          selected === value ? "is-selected" : "",
        ].filter(Boolean).join(" ");
        return (
          <button
            key={`${value}-${item.label || ""}`}
            type="button"
            className={classes}
            disabled={!booked && item.bookable === false}
            onClick={() => {
              if (booked) {
                onBooked?.(item);
                return;
              }
              onSelect(value, false, !!item.exception);
            }}
          >
            <span>{item.label || value}</span>
            {booked ? <small>پر</small> : null}
            {item.removed && !booked ? <small>حذف‌شده</small> : null}
            {item.exception && !booked ? <small>استثنا</small> : null}
          </button>
        );
      })}
    </div>
  );
}
