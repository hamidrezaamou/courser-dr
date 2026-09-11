import type { ReactNode } from "react";

export function PageChrome({
  header,
  children,
  width = "7xl",
  bodyClass = "",
  padded = true,
  gap = "space-y-4",
}: {
  header?: ReactNode;
  children: ReactNode;
  width?: "7xl" | "6xl" | "5xl" | "3xl";
  bodyClass?: string;
  padded?: boolean;
  gap?: string;
}) {
  const widths = {
    "7xl": "mx-auto max-w-7xl px-4 sm:px-6 lg:px-8",
    "6xl": "mx-auto max-w-6xl px-4 sm:px-6 lg:px-8",
    "5xl": "mx-auto max-w-5xl px-4 sm:px-6 lg:px-8",
    "3xl": "mx-auto max-w-3xl px-4 sm:px-6 lg:px-8",
  };
  return (
    <>
      {header ? (
        <header className="page-topbar border-b backdrop-blur-sm">
          <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 fade-up">{header}</div>
        </header>
      ) : null}
      <div className={bodyClass}>
        <div className={`${widths[width]}${padded ? " py-4 sm:py-8" : ""} ${gap}`}>{children}</div>
      </div>
    </>
  );
}
