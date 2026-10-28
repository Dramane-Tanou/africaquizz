import type { ReactNode } from "react";
import { Link } from "react-router-dom";
import { useTranslation } from "react-i18next";
import { Logo } from "../../components/ui/Brand";
import { OfflineBanner } from "../../components/layout/Banners";

export default function AuthLayout({ title, intro, children, footer }: { title: string; intro?: string; children: ReactNode; footer?: ReactNode }) {
  const { t } = useTranslation();
  return (
    <div className="pattern-bg min-h-[100dvh]">
      <OfflineBanner />
      <div className="mx-auto flex min-h-[100dvh] max-w-md flex-col px-5 py-6">
        <div className="flex items-center justify-between">
          <Link to="/" aria-label={t("app.goHome")} className="rounded-xl">
            <Logo size={32} />
          </Link>
        </div>
        <div className="flex flex-1 flex-col justify-center py-8">
          <div className="rounded-bubble border-2 border-black bg-surface p-6 shadow-flat dark:border-line">
            <h1 className="font-title text-3xl font-bold">{title}</h1>
            {intro && <p className="mt-1 text-ink-2">{intro}</p>}
            <div className="mt-6">{children}</div>
          </div>
          {footer && <div className="mt-6 text-center text-sm">{footer}</div>}
        </div>
      </div>
    </div>
  );
}
