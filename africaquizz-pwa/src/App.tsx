import { lazy, Suspense, type ReactNode } from "react";
import { Navigate, Route, Routes, useLocation } from "react-router-dom";
import { useTranslation } from "react-i18next";
import { useAuth } from "./context/AuthContext";
import AppShell from "./components/layout/AppShell";
import { ButtonLink, FullPageSpinner } from "./components/ui/primitives";
import { LogoMark } from "./components/ui/Brand";
import Home from "./pages/Home";
import Onboarding from "./pages/Onboarding";
import Play from "./pages/quiz/Play";
import Result from "./pages/quiz/Result";
import Login from "./pages/auth/Login";
import Register from "./pages/auth/Register";

// Écrans secondaires chargés à la demande (bundle initial plus léger pour les réseaux lents).
const Leaderboard = lazy(() => import("./pages/Leaderboard"));
const Profile = lazy(() => import("./pages/Profile"));
const Revision = lazy(() => import("./pages/Revision"));
const Settings = lazy(() => import("./pages/Settings"));
const DailyChallenge = lazy(() => import("./pages/DailyChallenge"));
const ForgotPassword = lazy(() => import("./pages/auth/Account").then((m) => ({ default: m.ForgotPassword })));
const ResetPassword = lazy(() => import("./pages/auth/Account").then((m) => ({ default: m.ResetPassword })));
const VerifyEmail = lazy(() => import("./pages/auth/Account").then((m) => ({ default: m.VerifyEmail })));
const AdminLogin = lazy(() => import("./pages/admin/AdminLogin"));
const AdminConsole = lazy(() => import("./pages/admin/AdminConsole"));

export default function App() {
  const { isLoading, isAuthenticated, onboarded } = useAuth();

  if (isLoading) return <Splash />;

  return (
    <Suspense fallback={<FullPageSpinner />}>
      <Routes>
        <Route element={<AppShell />}>
          <Route index element={isAuthenticated || onboarded ? <Home /> : <Navigate to="/bienvenue" replace />} />
          <Route path="classements" element={<Leaderboard />} />
          <Route path="defi-du-jour" element={<DailyChallenge />} />
          <Route path="revision" element={<Revision />} />
          <Route path="profil" element={<Profile />} />
          <Route path="parametres" element={<Settings />} />
          <Route path="*" element={<NotFound />} />
        </Route>
        <Route path="bienvenue" element={<Onboarding />} />
        <Route path="jouer/:id" element={<Play />} />
        <Route path="resultats/:id" element={<Result />} />
        <Route path="connexion" element={<GuestOnly><Login /></GuestOnly>} />
        <Route path="inscription" element={<GuestOnly><Register /></GuestOnly>} />
        <Route path="mot-de-passe-oublie" element={<ForgotPassword />} />
        <Route path="reinitialiser" element={<ResetPassword />} />
        <Route path="verifier-email" element={<VerifyEmail />} />
        <Route path="admin/connexion" element={<AdminLogin />} />
        <Route path="admin" element={<AdminConsole />} />
        <Route path="admin/:role" element={<AdminConsole />} />
      </Routes>
    </Suspense>
  );
}

function GuestOnly({ children }: { children: ReactNode }) {
  const { isAuthenticated } = useAuth();
  const location = useLocation();
  if (isAuthenticated) return <Navigate to={(location.state as { from?: string } | null)?.from ?? "/"} replace />;
  return <>{children}</>;
}

function Splash() {
  const { t } = useTranslation();
  return (
    <div className="pattern-bg flex min-h-[100dvh] flex-col items-center justify-center gap-4" role="status">
      <LogoMark size={96} className="animate-pulse motion-reduce:animate-none" />
      <p className="font-title text-xl font-semibold text-ink-2">{t("app.loading")}</p>
    </div>
  );
}

function NotFound() {
  const { t } = useTranslation();
  return (
    <div className="flex flex-col items-center gap-4 py-16 text-center">
      <p className="text-5xl" aria-hidden>
        🧭
      </p>
      <p className="font-title text-2xl font-semibold">{t("app.notFound")}</p>
      <ButtonLink to="/">{t("app.goHome")}</ButtonLink>
    </div>
  );
}
