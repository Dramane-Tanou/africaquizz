import { NavLink, Outlet, Link } from "react-router-dom";
import { useTranslation } from "react-i18next";
import { Logo } from "../ui/Brand";
import { cx } from "../ui/primitives";
import { InstallBanner, OfflineBanner, VerifyBanner } from "./Banners";

const Icon = {
  home: <path d="M3 11 12 4l9 7v9a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z" />,
  trophy: <path d="M7 4h10v3a5 5 0 0 1-10 0zM4 5h3v2a3 3 0 0 1-3-2zm16 0h-3v2a3 3 0 0 0 3-2zM10 13h4v3h3v4H7v-4h3z" />,
  repeat: <path d="M4 12a7 7 0 0 1 12-5l2-2v6h-6l2.3-2.3A5 5 0 0 0 6 12zm16 0a7 7 0 0 1-12 5l-2 2v-6h6l-2.3 2.3A5 5 0 0 0 18 12z" />,
  user: <path d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8zm-8 9a8 8 0 0 1 16 0z" />,
  gear: (
    <path d="M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8zm9 4-2.1-.7a7 7 0 0 0-.6-1.5l1-2-1.6-1.6-2 1a7 7 0 0 0-1.5-.6L13.5 3h-3l-.7 2.1a7 7 0 0 0-1.5.6l-2-1L4.7 6.3l1 2a7 7 0 0 0-.6 1.5L3 10.5v3l2.1.7a7 7 0 0 0 .6 1.5l-1 2 1.6 1.6 2-1a7 7 0 0 0 1.5.6l.7 2.1h3l.7-2.1a7 7 0 0 0 1.5-.6l2 1 1.6-1.6-1-2a7 7 0 0 0 .6-1.5L21 13.5z" />
  ),
};

function Svg({ children }: { children: React.ReactNode }) {
  return (
    <svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor" aria-hidden>
      {children}
    </svg>
  );
}

export default function AppShell() {
  const { t } = useTranslation();
  const tabs = [
    { to: "/", label: t("nav.home"), icon: Icon.home, end: true },
    { to: "/classements", label: t("nav.leaderboard"), icon: Icon.trophy },
    { to: "/revision", label: t("nav.revision"), icon: Icon.repeat },
    { to: "/profil", label: t("nav.profile"), icon: Icon.user },
  ];
  return (
    <div className="mx-auto flex min-h-[100dvh] max-w-xl flex-col">
      <header className="sticky top-0 z-20 flex items-center justify-between border-b-2 border-black/10 bg-bg/90 px-4 py-3 backdrop-blur dark:border-line">
        <Link to="/" aria-label={t("nav.home")} className="rounded-xl">
          <Logo size={32} />
        </Link>
        <NavLink
          to="/parametres"
          aria-label={t("nav.settings")}
          className={({ isActive }) => cx("grid h-11 w-11 place-items-center rounded-full hover:bg-ink/5", isActive ? "text-brand-gold-dark" : "text-ink-2")}
        >
          <Svg>{Icon.gear}</Svg>
        </NavLink>
      </header>
      <OfflineBanner />
      <VerifyBanner />
      <InstallBanner />
      <main id="main" className="flex-1 px-4 pb-28 pt-4">
        <Outlet />
      </main>
      <nav
        aria-label="Navigation"
        className="fixed inset-x-0 bottom-0 z-20 border-t-2 border-black bg-surface pb-[env(safe-area-inset-bottom)] dark:border-line"
      >
        <ul className="mx-auto grid max-w-xl grid-cols-4">
          {tabs.map((tab) => (
            <li key={tab.to}>
              <NavLink
                to={tab.to}
                end={tab.end}
                className={({ isActive }) =>
                  cx(
                    "flex min-h-[60px] flex-col items-center justify-center gap-0.5 text-xs font-bold transition-colors",
                    isActive ? "text-ink" : "text-ink-2 hover:text-ink",
                  )
                }
              >
                {({ isActive }) => (
                  <>
                    <span className={cx("grid h-8 w-12 place-items-center rounded-full transition-colors", isActive && "bg-brand-gold text-black")}>
                      <Svg>{tab.icon}</Svg>
                    </span>
                    {tab.label}
                  </>
                )}
              </NavLink>
            </li>
          ))}
        </ul>
      </nav>
    </div>
  );
}
