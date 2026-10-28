import { useState, type FormEvent } from "react";
import { Link, useNavigate } from "react-router-dom";
import { useTranslation } from "react-i18next";
import { Alert, Button, Card, Field, PageTitle } from "../../components/ui/primitives";
import { ADMIN_TOKEN_KEY, adminApi, adminErrorMessage } from "../../lib/adminApi";
import { adminPathForRole } from "./adminRoles";

type Admin = { id: string; name: string; email: string; role: { name: string; label_fr: string; label_en: string } };
type Credentials = { email: string; password: string };
type LoginResponse = {
  data: {
    token?: string;
    temp_token?: string;
    requires_2fa_setup?: boolean;
    requires_2fa_code?: boolean;
    admin?: Admin;
    warning?: string;
  };
};
type SetupResponse = { data: { manual_entry: string; qr_url: string } };
type VerifyResponse = { data: { token: string; admin: Admin } };

type Stage = "credentials" | "setup" | "verify";

export default function AdminLogin() {
  const { t } = useTranslation();
  const navigate = useNavigate();
  const [credentials, setCredentials] = useState<Credentials>({ email: "", password: "" });
  const [stage, setStage] = useState<Stage>("credentials");
  const [tempToken, setTempToken] = useState("");
  const [secret, setSecret] = useState("");
  const [code, setCode] = useState("");
  const [error, setError] = useState("");
  const [busy, setBusy] = useState(false);
  const [recoveryCodes, setRecoveryCodes] = useState<string[]>([]);

  async function submitCredentials(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setBusy(true);
    setError("");
    try {
      const { data } = await adminApi.post<LoginResponse>("/auth/login", credentials);
      if (data.data.token) {
        sessionStorage.setItem(ADMIN_TOKEN_KEY, data.data.token);
        navigate(adminPathForRole(data.data.admin?.role.name ?? ""), { replace: true });
      } else if (data.data.requires_2fa_setup && data.data.temp_token) {
        setTempToken(data.data.temp_token);
        const setup = await adminApi.post<SetupResponse>("/auth/2fa/setup", {}, {
          headers: { Authorization: `Bearer ${data.data.temp_token}` },
        });
        setSecret(setup.data.data.manual_entry);
        setStage("setup");
      } else if (data.data.requires_2fa_code && data.data.temp_token) {
        setTempToken(data.data.temp_token);
        setStage("verify");
      } else {
        setError(t("admin.unexpectedLogin"));
      }
    } catch (requestError) {
      setError(adminErrorMessage(requestError, t("admin.loginError")));
    } finally {
      setBusy(false);
    }
  }

  async function confirmSetup(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setBusy(true);
    setError("");
    try {
      const { data } = await adminApi.post<{ data: { recovery_codes: string[] } }>("/auth/2fa/confirm", { code }, {
        headers: { Authorization: `Bearer ${tempToken}` },
      });
      setRecoveryCodes(data.data.recovery_codes);
      const nextLogin = await adminApi.post<LoginResponse>("/auth/login", credentials);
      if (!nextLogin.data.data.requires_2fa_code || !nextLogin.data.data.temp_token) {
        setError(t("admin.unexpectedLogin"));
        return;
      }
      setTempToken(nextLogin.data.data.temp_token);
      setCode("");
      setStage("verify");
    } catch (requestError) {
      setError(adminErrorMessage(requestError, t("admin.setupError")));
    } finally {
      setBusy(false);
    }
  }

  async function verifyCode(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setBusy(true);
    setError("");
    try {
      const { data } = await adminApi.post<VerifyResponse>("/auth/2fa/verify", { code }, {
        headers: { Authorization: `Bearer ${tempToken}` },
      });
      setCredentials({ email: "", password: "" });
      sessionStorage.setItem(ADMIN_TOKEN_KEY, data.data.token);
      navigate(adminPathForRole(data.data.admin.role.name), { replace: true });
    } catch (requestError) {
      setError(adminErrorMessage(requestError, t("admin.codeError")));
    } finally {
      setBusy(false);
    }
  }

  const roleLabels: Record<string, string> = {
    super_admin: t("admin.roleSuper"),
    admin: t("admin.roleAdmin"),
    relecteur: t("admin.roleReviewer"),
    editeur: t("admin.roleEditor"),
  };

  return (
    <main className="pattern-bg flex min-h-[100dvh] items-center justify-center px-4 py-10">
      <div className="w-full max-w-lg space-y-5">
        <Link to="/bienvenue" className="text-sm font-bold text-ink-2 hover:text-ink">
          ← {t("admin.backToPlayer")}
        </Link>
        <Card className="space-y-5">
          <PageTitle sub={t("admin.subtitle")}>{t("admin.title")}</PageTitle>
          <div className="grid grid-cols-2 gap-2 text-center text-xs font-bold sm:grid-cols-4">
            {Object.entries(roleLabels).map(([role, label]) => (
              <span key={role} className="rounded-xl bg-ink/5 px-2 py-2">{label}</span>
            ))}
          </div>
          {error && <Alert tone="danger">{error}</Alert>}
          {stage === "credentials" && (
            <form className="space-y-4" onSubmit={submitCredentials}>
              <Field
                label={t("admin.email")}
                name="email"
                type="email"
                autoComplete="username"
                required
                value={credentials.email}
                onChange={(event) => setCredentials({ ...credentials, email: event.target.value })}
              />
              <Field
                label={t("admin.password")}
                name="password"
                type="password"
                autoComplete="current-password"
                required
                value={credentials.password}
                onChange={(event) => setCredentials({ ...credentials, password: event.target.value })}
              />
              <Button type="submit" block loading={busy}>{t("admin.signIn")}</Button>
            </form>
          )}
          {stage === "setup" && (
            <form className="space-y-4" onSubmit={confirmSetup}>
              <Alert tone="warning">{t("admin.setupIntro")}</Alert>
              <div>
                <p className="mb-2 text-sm font-bold">{t("admin.manualKey")}</p>
                <code className="block break-all rounded-xl bg-ink/5 p-3 text-sm">{secret}</code>
              </div>
              <Field
                label={t("admin.code")}
                name="code"
                inputMode="numeric"
                autoComplete="one-time-code"
                pattern="[0-9]{6}"
                minLength={6}
                maxLength={6}
                required
                value={code}
                onChange={(event) => setCode(event.target.value.replace(/\D/g, "").slice(0, 6))}
              />
              <Button type="submit" block loading={busy}>{t("admin.confirmSetup")}</Button>
            </form>
          )}
          {stage === "verify" && (
            <form className="space-y-4" onSubmit={verifyCode}>
              <p className="text-sm text-ink-2">{t("admin.verifyIntro")}</p>
              {recoveryCodes.length > 0 && (
                <Alert tone="warning">
                  <p>{t("admin.saveRecovery")}</p>
                  <code className="mt-2 grid grid-cols-2 gap-1">{recoveryCodes.map((item) => <span key={item}>{item}</span>)}</code>
                </Alert>
              )}
              <Field
                label={t("admin.code")}
                name="code"
                inputMode="numeric"
                autoComplete="one-time-code"
                pattern="[0-9]{6}"
                minLength={6}
                maxLength={6}
                required
                value={code}
                onChange={(event) => setCode(event.target.value.replace(/\D/g, "").slice(0, 6))}
              />
              <Button type="submit" block loading={busy}>{t("admin.verify")}</Button>
            </form>
          )}
          <p className="text-xs text-ink-2">{t("admin.securityNote")}</p>
        </Card>
      </div>
    </main>
  );
}
