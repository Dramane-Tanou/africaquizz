import { useState, type FormEvent } from "react";
import { Link, useLocation, useNavigate } from "react-router-dom";
import { useTranslation } from "react-i18next";
import { useAuth } from "../../context/AuthContext";
import { normalizeError } from "../../lib/api";
import { useErrorMessage } from "../../hooks/useCommon";
import { Alert, Button, Field } from "../../components/ui/primitives";
import AuthLayout from "./AuthLayout";

export default function Login() {
  const { t } = useTranslation();
  const { login } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();
  const errMsg = useErrorMessage();
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const from = (location.state as { from?: string } | null)?.from ?? "/";

  async function onSubmit(e: FormEvent) {
    e.preventDefault();
    setLoading(true);
    setError(null);
    try {
      await login({ email: email.trim(), password });
      navigate(from, { replace: true });
    } catch (err) {
      const n = normalizeError(err);
      if (n.status === 401) setError(t("auth.invalidCredentials"));
      else if (n.status === 403) setError(t("auth.banned"));
      else setError(errMsg(err));
    } finally {
      setLoading(false);
    }
  }

  return (
    <AuthLayout
      title={t("auth.login")}
      footer={
        <>
          {t("auth.noAccount")}{" "}
          <Link to="/inscription" className="font-bold text-ink underline underline-offset-4">
            {t("auth.register")}
          </Link>
        </>
      }
    >
      <form onSubmit={onSubmit} className="space-y-4" noValidate>
        <Field label={t("auth.email")} name="email" type="email" autoComplete="email" required value={email} onChange={(e) => setEmail(e.target.value)} />
        <Field
          label={t("auth.password")}
          name="password"
          type="password"
          autoComplete="current-password"
          required
          value={password}
          onChange={(e) => setPassword(e.target.value)}
        />
        {error && <Alert tone="danger">{error}</Alert>}
        <Button type="submit" block loading={loading} disabled={!email || !password}>
          {t("auth.signIn")}
        </Button>
        <Link to="/mot-de-passe-oublie" className="block text-center text-sm font-semibold text-ink-2 underline underline-offset-4">
          {t("auth.forgot")}
        </Link>
      </form>
    </AuthLayout>
  );
}
