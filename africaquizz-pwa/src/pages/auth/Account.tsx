import { useState, type FormEvent } from "react";
import { Link, useNavigate, useSearchParams } from "react-router-dom";
import { useTranslation } from "react-i18next";
import { authApi } from "../../lib/endpoints";
import { normalizeError } from "../../lib/api";
import { useAuth } from "../../context/AuthContext";
import { useErrorMessage } from "../../hooks/useCommon";
import { Alert, Button, Field } from "../../components/ui/primitives";
import AuthLayout from "./AuthLayout";

export function ForgotPassword() {
  const { t } = useTranslation();
  const errMsg = useErrorMessage();
  const [email, setEmail] = useState("");
  const [loading, setLoading] = useState(false);
  const [done, setDone] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);

  async function onSubmit(e: FormEvent) {
    e.preventDefault();
    setLoading(true);
    setError(null);
    try {
      const r = await authApi.forgotPassword(email.trim());
      setDone(r.data.message);
    } catch (err) {
      setError(errMsg(err));
    } finally {
      setLoading(false);
    }
  }

  return (
    <AuthLayout title={t("auth.forgotTitle")} intro={t("auth.forgotIntro")} footer={<Link to="/connexion" className="font-bold underline underline-offset-4">{t("auth.login")}</Link>}>
      {done ? (
        <div className="space-y-4">
          <Alert tone="success">{done}</Alert>
          <Link to={`/reinitialiser?email=${encodeURIComponent(email.trim())}`} className="block text-center font-bold underline underline-offset-4">
            {t("auth.resetTitle")} →
          </Link>
        </div>
      ) : (
        <form onSubmit={onSubmit} className="space-y-4" noValidate>
          <Field label={t("auth.email")} name="email" type="email" autoComplete="email" value={email} onChange={(e) => setEmail(e.target.value)} />
          {error && <Alert tone="danger">{error}</Alert>}
          <Button type="submit" block loading={loading} disabled={!email}>
            {t("auth.sendLink")}
          </Button>
        </form>
      )}
    </AuthLayout>
  );
}

export function ResetPassword() {
  const { t } = useTranslation();
  const [params] = useSearchParams();
  const navigate = useNavigate();
  const errMsg = useErrorMessage();
  const [form, setForm] = useState({ token: params.get("token") ?? "", email: params.get("email") ?? "", password: "", password_confirmation: "" });
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [done, setDone] = useState(false);
  const set = (k: keyof typeof form) => (e: React.ChangeEvent<HTMLInputElement>) => setForm((f) => ({ ...f, [k]: e.target.value }));
  const mismatch = form.password_confirmation && form.password !== form.password_confirmation;

  async function onSubmit(e: FormEvent) {
    e.preventDefault();
    setLoading(true);
    setError(null);
    try {
      await authApi.resetPassword({ ...form, token: form.token.trim(), email: form.email.trim() });
      setDone(true);
      setTimeout(() => navigate("/connexion"), 1500);
    } catch (err) {
      setError(errMsg(err));
    } finally {
      setLoading(false);
    }
  }

  return (
    <AuthLayout title={t("auth.resetTitle")}>
      {done ? (
        <Alert tone="success">{t("auth.resetDone")}</Alert>
      ) : (
        <form onSubmit={onSubmit} className="space-y-4" noValidate>
          <Field label={t("auth.email")} name="email" type="email" value={form.email} onChange={set("email")} />
          <Field label={t("auth.resetToken")} name="token" value={form.token} onChange={set("token")} autoComplete="one-time-code" />
          <Field label={t("auth.password")} name="password" type="password" autoComplete="new-password" hint={t("auth.passwordMin")} value={form.password} onChange={set("password")} />
          <Field
            label={t("auth.passwordConfirm")}
            name="password_confirmation"
            type="password"
            autoComplete="new-password"
            value={form.password_confirmation}
            onChange={set("password_confirmation")}
            error={mismatch ? t("auth.passwordMismatch") : undefined}
          />
          {error && <Alert tone="danger">{error}</Alert>}
          <Button type="submit" block loading={loading} disabled={!form.token || !form.email || form.password.length < 8 || !!mismatch}>
            {t("app.save")}
          </Button>
        </form>
      )}
    </AuthLayout>
  );
}

export function VerifyEmail() {
  const { t } = useTranslation();
  const [params] = useSearchParams();
  const { isAuthenticated, refresh } = useAuth();
  const errMsg = useErrorMessage();
  const [token, setToken] = useState(params.get("token") ?? "");
  const [loading, setLoading] = useState(false);
  const [msg, setMsg] = useState<{ tone: "success" | "danger" | "info"; text: string } | null>(null);

  async function onSubmit(e: FormEvent) {
    e.preventDefault();
    setLoading(true);
    setMsg(null);
    try {
      await authApi.verifyEmail(token.trim());
      setMsg({ tone: "success", text: t("auth.verifyDone") });
      refresh();
    } catch (err) {
      setMsg({ tone: "danger", text: errMsg(err) });
    } finally {
      setLoading(false);
    }
  }

  async function resend() {
    try {
      await authApi.resendVerification();
      setMsg({ tone: "info", text: t("auth.resent") });
    } catch (err) {
      const n = normalizeError(err);
      setMsg({ tone: "danger", text: n.message || errMsg(err) });
    }
  }

  return (
    <AuthLayout title={t("auth.verifyTitle")} intro={t("auth.verifyIntro")} footer={<Link to="/" className="font-bold underline underline-offset-4">{t("app.goHome")}</Link>}>
      <form onSubmit={onSubmit} className="space-y-4" noValidate>
        <Field label={t("auth.verifyCode")} name="token" value={token} onChange={(e) => setToken(e.target.value)} autoComplete="one-time-code" />
        {msg && <Alert tone={msg.tone}>{msg.text}</Alert>}
        <Button type="submit" block loading={loading} disabled={!token.trim()}>
          {t("auth.verifyAction")}
        </Button>
        {isAuthenticated && (
          <Button type="button" variant="ghost" block onClick={() => void resend()}>
            {t("auth.resend")}
          </Button>
        )}
      </form>
    </AuthLayout>
  );
}
