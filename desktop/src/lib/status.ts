export function statusColor(status: string | null | undefined): string {
  switch (status) {
    case "confirmed":
      return "var(--success)";
    case "done":
      return "var(--brand-dark)";
    case "cancelled":
    case "no_show":
      return "var(--warm)";
    case "waiting":
    case "ready":
    case "in_consult":
      return "var(--waiting)";
    default:
      return "var(--muted)";
  }
}

export function joinMeta(parts: Array<string | null | undefined>): string {
  return parts.filter((part) => part && part.trim()).join("  ·  ");
}
