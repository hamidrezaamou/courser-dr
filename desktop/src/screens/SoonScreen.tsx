import { GhostButton } from "../components/ui";

export function SoonScreen({
  title,
  body,
  onBack,
}: {
  title: string;
  body: string;
  onBack: () => void;
}) {
  return (
    <div className="page narrow">
      <header className="page-head">
        <div>
          <h1>{title}</h1>
          <p className="lede">{body}</p>
        </div>
        <GhostButton onClick={onBack}>داشبورد</GhostButton>
      </header>
      <div className="card">
        <p className="hint">
          پوسته ویندوز آماده است. این بخش در فاز بعدی روی همین ایستگاه، آفلاین‌اول، اضافه می‌شود — نه با باز کردن سایت.
        </p>
      </div>
    </div>
  );
}
