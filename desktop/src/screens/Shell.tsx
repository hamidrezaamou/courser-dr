import { useEffect, useState } from "react";
import { ThemeToggle } from "../components/ThemeToggle";
import { loadPanel, prefetchCatalogs } from "../lib/api";
import { flushOutbox, pendingCount, rejectedCount, subscribeIdMap, subscribeOutbox } from "../lib/outbox";
import type { PanelBootstrap, Route, Session } from "../lib/types";
import { AccountScreen } from "./AccountScreen";
import { BoardScreen } from "./BoardScreen";
import { BookSurgeryScreen } from "./BookSurgeryScreen";
import { BookVisitScreen } from "./BookVisitScreen";
import { CreatePatientScreen } from "./CreatePatientScreen";
import { EditPatientScreen } from "./EditPatientScreen";
import { FloorScreen } from "./FloorScreen";
import { FollowupsScreen } from "./FollowupsScreen";
import { DashboardScreen } from "./PatientsScreen";
import { PatientDetailScreen } from "./PatientDetailScreen";
import { ReportsScreen } from "./ReportsScreen";
import { AdminScreen, ModulesScreen, PrintsScreen, SettingsScreen } from "./SettingsScreen";
import {
  AccountingScreen,
  AdminUsersScreen,
  ApprovalScreen,
  FeaturesScreen,
  MessagesScreen,
  QuickLinksScreen,
  WaitingScreen,
} from "./ClinicPages";
import {
  AuditScreen,
  BillingScreen,
  BrandScreen,
  CommunicationsScreen,
  ConsentScreen,
  EyeChartScreen,
  HisScreen,
  PortalScreen,
  QualityScreen,
  RxPrintScreen,
} from "./MoreClinicPages";
import { loadMessages } from "../lib/ops";
import {
  ReadyAnswersScreen,
  SupportScreen,
  SystemScreen,
} from "./ClinicToolsPages";
type NavItem = {
  id: string;
  label: string;
  route: Route;
  show: (session: Session, panel: PanelBootstrap | null) => boolean;
};

const NAV: NavItem[] = [
  { id: "dashboard", label: "داشبورد", route: { name: "dashboard" }, show: (session) => !!session.user.is_staff },
  { id: "board", label: "نوبت‌ها", route: { name: "board" }, show: (session) => !!session.user.is_staff },
  {
    id: "floor",
    label: "صف مطب",
    route: { name: "floor" },
    show: (session, panel) => !!session.user.is_staff && panel?.flags?.clinic_floor !== false,
  },
  {
    id: "reports",
    label: "گزارشات",
    route: { name: "reports" },
    show: (session) => !!session.user.is_staff && session.user.can_view_reports !== false,
  },
  {
    id: "followups",
    label: "پیگیری",
    route: { name: "followups" },
    show: (session, panel) => !!session.user.is_staff && panel?.flags?.followups !== false,
  },
  { id: "messages", label: "پیام‌ها", route: { name: "messages" }, show: (session) => !!session.user.is_staff },
  { id: "book-surgery", label: "ثبت عمل", route: { name: "book-surgery" }, show: (session) => !!session.user.is_staff },
  { id: "book-visit", label: "ثبت ویزیت", route: { name: "book-visit" }, show: (session) => !!session.user.is_staff },
  { id: "create-patient", label: "ثبت بیمار", route: { name: "create-patient" }, show: (session) => !!session.user.can_edit_patient },
  {
    id: "settings",
    label: "تنظیمات",
    route: { name: "settings", section: "times" },
    show: (session) => !!session.user.can_access_clinic_settings || !!session.user.can_manage_settings,
  },
  {
    id: "modules",
    label: "ماژول‌ها",
    route: { name: "modules" },
    show: (session, panel) => !!session.user.can_access_modules && panel?.flags?.modules !== false,
  },
];

function activeId(route: Route): string {
  if (route.name === "edit-patient") return "";
  if (route.name === "patient") return "";
  if (route.name === "settings") return "settings";
  if (route.name === "prints") return "spark";
  if (route.name === "admin") return "admin";
  if (route.name === "modules") return "modules";
  if (route.name === "messages") return "messages";
  if (route.name === "ready-answers") return "messages";
  if (route.name === "book-visit" && route.bookingId) return "";
  if (route.name === "book-surgery" && route.bookingId) return "";
  return route.name;
}

function openClinicLink(site: string, url: string) {
  const href = url.startsWith("/") ? `${site.replace(/\/$/, "")}${url}` : url;
  window.open(href, "_blank", "noopener,noreferrer");
}

export function Shell({
  session,
  onSession,
  onLogout,
}: {
  session: Session;
  onSession: (session: Session) => void;
  onLogout: () => void;
}) {
  const staff = !!session.user.is_staff;
  const [route, setRoute] = useState<Route>({ name: "dashboard" });
  const [menuOpen, setMenuOpen] = useState(false);
  const [accountOpen, setAccountOpen] = useState(false);
  const [sparkOpen, setSparkOpen] = useState(false);
  const [online, setOnline] = useState(navigator.onLine);
  const [queue, setQueue] = useState(() => pendingCount());
  const [rejected, setRejected] = useState(() => rejectedCount());
  const [panel, setPanel] = useState<PanelBootstrap | null>(null);
  const [unread, setUnread] = useState(0);

  useEffect(() => {
    void prefetchCatalogs(session);
    void loadPanel(session)
      .then((result) => setPanel(result.data))
      .catch(() =>
        setPanel({
          flags: {
            clinic_floor: true,
            followups: true,
            modules: !!session.user.can_access_modules,
          },
        }),
      );
    void flushOutbox(session);
    const refreshAlerts = () => {
      void loadMessages(session, "unread").then((data) => setUnread(data.unread || 0)).catch(() => undefined);
    };
    refreshAlerts();
    const alertsTimer = window.setInterval(refreshAlerts, 45000);
    const timer = window.setInterval(() => void flushOutbox(session), 20000);
    const on = () => {
      setOnline(true);
      void flushOutbox(session);
    };
    const off = () => setOnline(false);
    window.addEventListener("online", on);
    window.addEventListener("offline", off);
    const stopOutbox = subscribeOutbox(() => {
      setQueue(pendingCount());
      setRejected(rejectedCount());
    });
    const stopMap = subscribeIdMap((from, to) => {
      setRoute((current) => (current.name === "patient" && current.id === from ? { name: "patient", id: to } : current));
    });
    return () => {
      window.clearInterval(timer);
      window.clearInterval(alertsTimer);
      window.removeEventListener("online", on);
      window.removeEventListener("offline", off);
      stopOutbox();
      stopMap();
    };
  }, [session]);

  useEffect(() => {
    function onKey(event: KeyboardEvent) {
      if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === "k") {
        event.preventDefault();
        setRoute({ name: "dashboard" });
        window.setTimeout(() => window.dispatchEvent(new Event("focus-dash-search")), 0);
      }
      if (event.key === "F2" && staff && session.user.can_edit_patient) {
        event.preventDefault();
        setRoute({ name: "create-patient" });
      }
      if (event.key === "F3" && staff) {
        event.preventDefault();
        setRoute({ name: "board" });
      }
      if (event.key === "F4" && staff) {
        event.preventDefault();
        setRoute(route.name === "patient" ? { name: "book-visit", patientId: route.id } : { name: "book-visit" });
      }
      if (event.key === "F5" && staff) {
        event.preventDefault();
        setRoute(route.name === "patient" ? { name: "book-surgery", patientId: route.id } : { name: "book-surgery" });
      }
    }
    window.addEventListener("keydown", onKey);
    return () => window.removeEventListener("keydown", onKey);
  }, [staff, session.user.can_edit_patient, route]);

  const current = activeId(route);
  const links = NAV.filter((item) => item.show(session, panel));
  const sparkLinks = panel?.quick_links || [];
  const showSpark = !!session.user.can_manage_settings;

  function go(next: Route) {
    setRoute(next);
    setMenuOpen(false);
    setAccountOpen(false);
    setSparkOpen(false);
  }

  const bookingNav = {
    onOpenPatient: (id: number) => go({ name: "patient" as const, id }),
    onBookVisit: (id?: number) => go(id ? { name: "book-visit" as const, patientId: id } : { name: "book-visit" as const }),
    onBookSurgery: (id?: number) => go(id ? { name: "book-surgery" as const, patientId: id } : { name: "book-surgery" as const }),
    onPrints: (surgeryId?: number) => go(surgeryId ? { name: "prints" as const, surgeryId } : { name: "prints" as const }),
    onEdit: (kind: "visit" | "surgery", id: number) =>
      go(kind === "surgery" ? { name: "book-surgery" as const, bookingId: id } : { name: "book-visit" as const, bookingId: id }),
  };

  const afterBooking = (patientId?: number) => {
    go(patientId ? { name: "patient", id: patientId } : { name: "board" });
  };

  return (
    <div className="app-shell relative">
      <div className="relative z-10">
      <nav className="nav-shell">
        <div className="max-w-[90rem] mx-auto px-3 sm:px-6 lg:px-8">
          <div className="nav-shell__row flex justify-between">
            <div className="flex min-w-0 items-center gap-2 sm:gap-3 lg:gap-6">
            <button type="button" className="brand-mark shrink-0" onClick={() => go({ name: "dashboard" })}>
              <span className="brand-mark__icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8">
                  <path strokeLinecap="round" strokeLinejoin="round" d="M12 21s-7-4.35-7-10a4 4 0 017-2.65A4 4 0 0119 11c0 5.65-7 10-7 10z" />
                </svg>
              </span>
              <span className="hidden md:block text-right">
                <span className="brand-mark__title block">آرشیو بیمار</span>
                <span className="brand-mark__subtitle block">پرونده الکترونیک</span>
              </span>
            </button>
            <div className="nav-desktop-links hidden lg:flex items-center gap-0.5 xl:gap-1">
              {links.map((item) => (
                <button
                  key={item.id}
                  type="button"
                  className={current === item.id ? "nav-link is-active" : "nav-link"}
                  onClick={() => go(item.route)}
                >
                  {item.label}
                  {item.id === "messages" && unread > 0 ? <span className="nav-badge">{unread}</span> : null}
                </button>
              ))}
              {showSpark ? (
                <div className="nav-spark">
                  <button
                    type="button"
                    className={sparkOpen || current === "spark" ? "nav-spark__btn is-open" : "nav-spark__btn"}
                    onClick={() => {
                      setSparkOpen((open) => !open);
                      setAccountOpen(false);
                    }}
                  >
                    <svg className="nav-spark__icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                      <path d="M12 3.2l1.6 4.4 4.6.2-3.6 2.9 1.2 4.4L12 12.8 8.2 15.1l1.2-4.4-3.6-2.9 4.6-.2L12 3.2z" fill="currentColor" />
                    </svg>
                    <span>ویژه</span>
                  </button>
                  {sparkOpen ? (
                    <div className="nav-spark__panel">
                      <div className="nav-spark__head">میانبرهای ویژه</div>
                      <button type="button" className="nav-spark__link" onClick={() => go({ name: "prints" })}>
                        پرینت‌ها
                      </button>
                      <button type="button" className="nav-spark__link" onClick={() => go({ name: "ready-answers" })}>
                        پاسخ‌های آماده
                      </button>
                      {sparkLinks.map((link) => (
                        <button
                          key={`${link.title}-${link.url}`}
                          type="button"
                          className="nav-spark__link"
                          onClick={() => openClinicLink(session.site, link.url)}
                        >
                          {link.title}
                        </button>
                      ))}
                      {sparkLinks.length === 0 ? <p className="nav-spark__empty">لینک سفارشی روی هاست ثبت نشده است.</p> : null}
                    </div>
                  ) : null}
                </div>
              ) : null}
            </div>
          </div>
          <div className="flex shrink-0 items-center gap-2">
            {queue > 0 ? (
              <button type="button" className="pill wait" onClick={() => go({ name: "account" })}>
                {queue} در انتظار ارسال
              </button>
            ) : null}
            {rejected > 0 ? (
              <button type="button" className="pill off" onClick={() => go({ name: "account" })}>
                {rejected} رد شد
              </button>
            ) : null}
            <span className={online ? "pill on" : "pill off"}>{online ? "آنلاین" : "آفلاین"}</span>
            <ThemeToggle />
            <div className="nav-user">
              <strong>{session.user.name}</strong>
              <span>{session.user.role_label}</span>
            </div>
            <div className="nav-account">
              <button
                type="button"
                className={session.user.can_manage_settings ? "btn-primary btn-compact" : "btn-ghost"}
                onClick={() => {
                  setAccountOpen((open) => !open);
                  setSparkOpen(false);
                }}
              >
                {session.user.can_manage_settings ? "مدیریت کل سایت" : "حساب"}
              </button>
              {accountOpen ? (
                <div className="nav-account__panel">
                  {session.user.can_manage_settings ? (
                    <>
                      <button type="button" onClick={() => go({ name: "admin" })}>
                        نمای کلی مدیریت
                      </button>
                      <button type="button" onClick={() => go({ name: "admin", section: "users" })}>
                        کاربران و نقش‌ها
                      </button>
                      <button type="button" onClick={() => go({ name: "admin", section: "features" })}>
                        قابلیت‌ها
                      </button>
                      <button type="button" onClick={() => go({ name: "admin", section: "quick-links" })}>
                        لینک‌های ویژه هدر
                      </button>
                      <button type="button" onClick={() => go({ name: "admin", section: "communications" })}>
                        ارتباطات و پیامک
                      </button>
                      <button type="button" onClick={() => go({ name: "admin", section: "brand" })}>
                        برند مطب
                      </button>
                      <button type="button" onClick={() => go({ name: "admin", section: "audit" })}>
                        ممیزی
                      </button>
                      <button type="button" onClick={() => go({ name: "admin", section: "his" })}>
                        همگام HIS
                      </button>
                      <button type="button" onClick={() => go({ name: "admin", section: "system" })}>
                        سیستم و پشتیبان
                      </button>
                      <button type="button" onClick={() => go({ name: "admin", section: "support" })}>
                        پشتیبانی
                      </button>
                      <button type="button" onClick={() => go({ name: "settings", section: "times" })}>
                        تنظیمات کلینیک
                      </button>
                      <button type="button" onClick={() => go({ name: "prints" })}>
                        برند و چاپ
                      </button>
                      {session.user.can_access_modules ? (
                        <button type="button" onClick={() => go({ name: "modules" })}>
                          ماژول‌های پیشرفته
                        </button>
                      ) : null}
                    </>
                  ) : null}
                  <button type="button" onClick={() => go({ name: "ready-answers" })}>
                    پاسخ‌های آماده
                  </button>
                  <button type="button" onClick={() => go({ name: "messages" })}>
                    پیام‌های پرونده
                  </button>
                  <button type="button" onClick={() => go({ name: "account" })}>
                    پروفایل و اتصال
                  </button>
                  <button type="button" onClick={onLogout}>
                    خروج
                  </button>
                </div>
              ) : null}
            </div>
            <button type="button" className="nav-icon-btn lg:hidden" onClick={() => setMenuOpen((open) => !open)} aria-label="منو">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path strokeLinecap="round" d="M4 7h16M4 12h16M4 17h16" />
              </svg>
            </button>
          </div>
        </div>
        </div>
        {menuOpen ? (
          <div className="lg:hidden border-t pt-2 pb-3 space-y-1 px-3" style={{ borderColor: "var(--line)", background: "var(--panel)" }}>
            {links.map((item) => (
              <button
                key={item.id}
                type="button"
                className={current === item.id ? "nav-link is-active" : "nav-link"}
                onClick={() => go(item.route)}
              >
                {item.label}
                {item.id === "messages" && unread > 0 ? <span className="nav-badge">{unread}</span> : null}
              </button>
            ))}
            {showSpark ? (
              <button type="button" className="nav-link" onClick={() => go({ name: "prints" })}>
                ویژه / پرینت‌ها
              </button>
            ) : null}
            {session.user.can_manage_settings ? (
              <button type="button" className="nav-link" onClick={() => go({ name: "admin" })}>
                مدیریت کل سایت
              </button>
            ) : null}
            <button type="button" className="nav-link" onClick={() => go({ name: "account" })}>
              حساب
            </button>
          </div>
        ) : null}
      </nav>

      <main className="main">
        {route.name === "dashboard" ? (
          <DashboardScreen
            session={session}
            onOpen={(id) => go({ name: "patient", id })}
            onCreate={() => go({ name: "create-patient" })}
            onBookVisit={(id) => go(id ? { name: "book-visit", patientId: id } : { name: "book-visit" })}
            onBookSurgery={(id) => go(id ? { name: "book-surgery", patientId: id } : { name: "book-surgery" })}
          />
        ) : null}
        {route.name === "create-patient" ? (
          <CreatePatientScreen
            session={session}
            onBack={() => go({ name: "dashboard" })}
            onCreated={(id) => go({ name: "patient", id })}
          />
        ) : null}
        {route.name === "edit-patient" ? (
          <EditPatientScreen
            session={session}
            patientId={route.id}
            onBack={() => go({ name: "patient", id: route.id })}
            onSaved={() => go({ name: "patient", id: route.id })}
          />
        ) : null}
        {route.name === "patient" ? (
          <PatientDetailScreen
            session={session}
            patientId={route.id}
            onBack={() => go({ name: "dashboard" })}
            onBookVisit={() => go({ name: "book-visit", patientId: route.id })}
            onBookSurgery={() => go({ name: "book-surgery", patientId: route.id })}
            onEdit={() => go({ name: "edit-patient", id: route.id })}
            onEditBooking={(kind, id) =>
              go(kind === "surgery" ? { name: "book-surgery", bookingId: id, patientId: route.id } : { name: "book-visit", bookingId: id, patientId: route.id })
            }
            onPrints={(surgeryId) => go({ name: "prints", surgeryId })}
          />
        ) : null}
        {route.name === "book-visit" ? (
          <BookVisitScreen
            session={session}
            patientId={route.patientId}
            bookingId={route.bookingId}
            onBack={() => go(route.patientId ? { name: "patient", id: route.patientId } : { name: "dashboard" })}
            onDone={afterBooking}
          />
        ) : null}
        {route.name === "book-surgery" ? (
          <BookSurgeryScreen
            session={session}
            patientId={route.patientId}
            bookingId={route.bookingId}
            prefill={route.prefill}
            onBack={() => go(route.patientId ? { name: "patient", id: route.patientId } : { name: "dashboard" })}
            onDone={afterBooking}
          />
        ) : null}
        {route.name === "board" ? (
          <BoardScreen session={session} nav={bookingNav} />
        ) : null}
        {route.name === "floor" ? (
          <FloorScreen session={session} nav={bookingNav} />
        ) : null}
        {route.name === "reports" ? (
          <ReportsScreen session={session} nav={bookingNav} />
        ) : null}
        {route.name === "followups" ? (
          <FollowupsScreen
            session={session}
            onOpen={(id) => go({ name: "patient", id })}
            onSettings={() => go({ name: "settings", section: "follow-ups" })}
          />
        ) : null}
        {route.name === "messages" ? (
          <MessagesScreen session={session} onOpen={(id) => go({ name: "patient", id })} />
        ) : null}
        {route.name === "settings" ? (
          <SettingsScreen
            session={session}
            section={route.section || "times"}
            onSection={(section) => go({ name: "settings", section })}
            onOpenPatient={(id) => go({ name: "patient", id })}
            onDashboard={() => go({ name: "dashboard" })}
            onFollowups={() => go({ name: "followups" })}
            onAdmin={() => go({ name: "admin" })}
          />
        ) : null}
        {route.name === "modules" && (!route.module || route.module === "hub") ? (
          <ModulesScreen session={session} onOpen={(module) => go({ name: "modules", module })} />
        ) : null}
        {route.name === "modules" && route.module === "waiting" ? (
          <WaitingScreen
            session={session}
            onBookVisit={(opts) => go({ name: "book-visit", patientId: opts?.patientId, bookingId: opts?.bookingId })}
            onBookSurgery={(opts) => go({ name: "book-surgery", patientId: opts?.patientId, prefill: opts?.prefill })}
          />
        ) : null}
        {route.name === "modules" && route.module === "accounting" ? <AccountingScreen session={session} /> : null}
        {route.name === "modules" && route.module === "approval" ? (
          <ApprovalScreen session={session} onOpen={(id) => go({ name: "patient", id })} />
        ) : null}
        {route.name === "modules" && route.module === "billing" ? <BillingScreen session={session} /> : null}
        {route.name === "modules" && route.module === "consent" ? <ConsentScreen session={session} /> : null}
        {route.name === "modules" && route.module === "portal" ? <PortalScreen session={session} /> : null}
        {route.name === "modules" && route.module === "quality" ? <QualityScreen session={session} /> : null}
        {route.name === "modules" && route.module === "eye_chart" ? (
          <EyeChartScreen session={session} onOpen={(id) => go({ name: "patient", id })} />
        ) : null}
        {route.name === "modules" && route.module === "rx_print" ? <RxPrintScreen session={session} /> : null}
        {route.name === "admin" && (!route.section || route.section === "overview") ? (
          <AdminScreen
            session={session}
            onUsers={() => go({ name: "admin", section: "users" })}
            onSettings={() => go({ name: "settings", section: "times" })}
            onModules={() => go({ name: "modules" })}
            onPrints={() => go({ name: "prints" })}
            onReports={() => go({ name: "reports" })}
            onFeatures={() => go({ name: "admin", section: "features" })}
            onQuickLinks={() => go({ name: "admin", section: "quick-links" })}
            onCommunications={() => go({ name: "admin", section: "communications" })}
            onBrand={() => go({ name: "admin", section: "brand" })}
            onAudit={() => go({ name: "admin", section: "audit" })}
            onHis={() => go({ name: "admin", section: "his" })}
            onSystem={() => go({ name: "admin", section: "system" })}
            onSupport={() => go({ name: "admin", section: "support" })}
          />
        ) : null}
        {route.name === "admin" && route.section === "users" ? <AdminUsersScreen session={session} /> : null}
        {route.name === "admin" && route.section === "features" ? <FeaturesScreen session={session} /> : null}
        {route.name === "admin" && route.section === "quick-links" ? <QuickLinksScreen session={session} /> : null}
        {route.name === "admin" && route.section === "communications" ? <CommunicationsScreen session={session} /> : null}
        {route.name === "admin" && route.section === "brand" ? <BrandScreen session={session} /> : null}
        {route.name === "admin" && route.section === "audit" ? <AuditScreen session={session} /> : null}
        {route.name === "admin" && route.section === "his" ? <HisScreen session={session} /> : null}
        {route.name === "admin" && route.section === "system" ? <SystemScreen session={session} /> : null}
        {route.name === "admin" && route.section === "support" ? <SupportScreen session={session} /> : null}
        {route.name === "ready-answers" ? <ReadyAnswersScreen session={session} /> : null}
        {route.name === "prints" ? (
          <PrintsScreen
            session={session}
            surgeryId={route.surgeryId}
            onOpenHub={(id) => go({ name: "prints", surgeryId: id })}
          />
        ) : null}
        {route.name === "account" ? (
          <AccountScreen
            session={session}
            onSiteSaved={onSession}
            onLoggedOut={onLogout}
            onFlush={() => void flushOutbox(session)}
          />
        ) : null}
      </main>
      </div>
    </div>
  );
}
