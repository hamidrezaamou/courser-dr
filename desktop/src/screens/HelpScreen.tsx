import { useEffect, useState } from "react";
import { ErrorBanner, LoadingBox } from "../components/ui";
import { loadHelp } from "../lib/ops";
import type { Session } from "../lib/types";

const FALLBACK = {
  sections: [
    {
      title: "برای منشی (۲ دقیقه)",
      items: [
        "بیمار را از داشبورد جستجو کنید؛ اگر نبود «ثبت بیمار».",
        "از پرونده یا منو، «ثبت ویزیت» / «ثبت عمل» را بزنید.",
        "در «نوبت‌ها» وضعیت را به‌روز کنید و در صورت نیاز پیامک بفرستید.",
        "از منوی «پیگیری» کارهای امروز و عقب‌افتاده را انجام دهید و نتیجه ثبت کنید.",
        "اگر تأیید آنلاین روشن است، درخواست‌های بنفش را تأیید/رد کنید.",
      ],
    },
    {
      title: "برای پزشک (۲ دقیقه)",
      items: [
        "از «صف مطب» بیمار ارجاع‌شده را باز کنید.",
        "معاینه، وایت‌برد و نسخه را در پرونده ثبت کنید.",
        "برای عمل، چک‌لیست و پرینت‌های بیمارستان را از ابزار ردیف بگیرید.",
        "الگوی پیگیری هر نوع/زیرگروه عمل را از تنظیمات → پیگیری بسازید تا با ثبت نوبت عمل خودکار ایجاد شود.",
        "خروجی گزارش و ممیزی در مدیریت در دسترس است.",
      ],
    },
    {
      title: "پشتیبان‌گیری و امنیت",
      items: [
        "بک‌آپ روزانه خودکار ساعت ۰۲:۳۰؛ دستی از «مدیریت → سامانه».",
        "بازیابی فقط برای مدیر؛ قبل از restore یک بک‌آپ تازه گرفته می‌شود.",
        "مشاهده پرونده در ممیزی ثبت می‌شود.",
        "حذف امن پرونده: فقط مدیر، با تأیید نام بیمار.",
      ],
    },
  ],
  retention_note: "پرونده‌ها طبق سیاست مطب نگه داشته می‌شوند. حذف امن فقط توسط مدیر انجام می‌شود.",
  support: {} as { telegram?: string; phone?: string; email?: string; sla_hours?: number; notes?: string },
};

export function HelpScreen({ session }: { session: Session }) {
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [data, setData] = useState(FALLBACK);

  useEffect(() => {
    let alive = true;
    (async () => {
      try {
        const result = await loadHelp(session);
        if (!alive) return;
        setData({
          sections: result.sections?.length ? result.sections : FALLBACK.sections,
          retention_note: result.retention_note || FALLBACK.retention_note,
          support: result.support || {},
        });
      } catch (err) {
        if (!alive) return;
        setError(err instanceof Error ? err.message : "راهنما از هاست نیامد؛ نسخه محلی نمایش داده شد.");
      } finally {
        if (alive) setLoading(false);
      }
    })();
    return () => {
      alive = false;
    };
  }, [session]);

  const support = data.support || {};
  const hasChannel = !!(support.telegram || support.phone || support.email);

  return (
    <div className="page narrow">
      <header className="page-head">
        <h1 className="page-title">راهنمای سریع</h1>
      </header>
      <ErrorBanner message={error} />
      {loading ? <LoadingBox /> : null}
      {data.sections.map((section) => (
        <section key={section.title} className="panel" style={{ marginBottom: 12, padding: 16 }}>
          <h3 style={{ margin: "0 0 8px" }}>{section.title}</h3>
          <ol className="help-list">
            {section.items.map((item) => (
              <li key={item}>{item}</li>
            ))}
          </ol>
        </section>
      ))}
      <section className="panel" style={{ marginBottom: 12, padding: 16 }}>
        <h3 style={{ margin: "0 0 8px" }}>پشتیبانی</h3>
        {support.telegram ? <p className="meta">تلگرام: <span dir="ltr">{support.telegram}</span></p> : null}
        {support.phone ? <p className="meta">تلفن: <span dir="ltr">{support.phone}</span></p> : null}
        {support.email ? <p className="meta">ایمیل: <span dir="ltr">{support.email}</span></p> : null}
        <p className="meta">SLA پاسخ: حداکثر {support.sla_hours ?? 24} ساعت کاری</p>
        {support.notes ? <p className="hint">{support.notes}</p> : null}
        {!hasChannel ? <p className="hint">کانال پشتیبانی هنوز تنظیم نشده — مدیر از «مدیریت → پشتیبانی» پر کند.</p> : null}
        {data.retention_note ? <p className="hint">{data.retention_note}</p> : null}
      </section>
    </div>
  );
}
