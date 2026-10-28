import { useMemo, useState, type FormEvent } from "react";
import { Link, useNavigate } from "react-router-dom";
import { useTranslation } from "react-i18next";
import { useAuth } from "../../context/AuthContext";
import { useSettings } from "../../context/SettingsContext";
import { normalizeError } from "../../lib/api";
import { ageFromDob } from "../../lib/game";
import { useErrorMessage } from "../../hooks/useCommon";
import { Alert, Button, Field } from "../../components/ui/primitives";
import AuthLayout from "./AuthLayout";

export default function Register() {
  const { t } = useTranslation();
  const { register } = useAuth();
  const { settings } = useSettings();
  const navigate = useNavigate();
  const errMsg = useErrorMessage();
  const [form, setForm] = useState({ name: "", email: "", password: "", password_confirmation: "", date_of_birth: "", parental_email: "" });
  const [touched, setTouched] = useState(false);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [serverErrors, setServerErrors] = useState<Record<string, string[]>>({});

  const age = useMemo(() => ageFromDob(form.date_of_birth), [form.date_of_birth]);
  const needsParent = age >= 13 && age <= 15;

  const clientErrors: Record<string, string | undefined> = {
    date_of_birth: Number.isFinite(age) && age < 13 ? t("auth.tooYoung") : undefined,
    password: form.password && form.password.length < 8 ? t("auth.passwordMin") : undefined,
    password_confirmation:
      form.password_confirmation && form.password !== form.password_confirmation ? t("auth.passwordMismatch") : undefined,
  };
  const fieldError = (k: keyof typeof form) => (touched ? clientErrors[k] : undefined) ?? serverErrors[k]?.[0];
  const valid =
    form.name.trim() &&
    form.email.trim() &&
    form.password.length >= 8 &&
    form.password === form.password_confirmation &&
    Number.isFinite(age) &&
    age >= 13 &&
    (!needsParent || form.parental_email.trim());

  const set = (k: keyof typeof form) => (e: React.ChangeEvent<HTMLInputElement>) => {
    setForm((f) => ({ ...f, [k]: e.target.value }));
    setServerErrors((s) => ({ ...s, [k]: [] }));
  };

  async function onSubmit(e: FormEvent) {
    e.preventDefault();
    setTouched(true);
    if (!valid) return;
    setLoading(true);
    setError(null);
    try {
      const { requiresParental } = await register({
        name: form.name.trim(),
        email: form.email.trim(),
        password: form.password,
        password_confirmation: form.password_confirmation,
        date_of_birth: form.date_of_birth,
        timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
        locale: settings.locale,
        ...(needsParent ? { parental_email: form.parental_email.trim() } : {}),
      });
      navigate("/", { replace: true, state: { welcome: true, parental: requiresParental } });
    } catch (err) {
      const n = normalizeError(err);
      if (n.fieldErrors) setServerErrors(n.fieldErrors);
      setError(n.status === 403 ? t("auth.tooYoung") : errMsg(err));
    } finally {
      setLoading(false);
    }
  }

  const today = new Date().toISOString().slice(0, 10);

  return (
    <AuthLayout
      title={t("auth.register")}
      intro={t("auth.saveProgress")}
      footer={
        <>
          {t("auth.haveAccount")}{" "}
          <Link to="/connexion" className="font-bold text-ink underline underline-offset-4">
            {t("auth.login")}
          </Link>
        </>
      }
    >
      <form onSubmit={onSubmit} className="space-y-4" noValidate>
        <Field label={t("auth.name")} name="name" autoComplete="given-name" required maxLength={255} value={form.name} onChange={set("name")} error={serverErrors.name?.[0]} />
        <Field label={t("auth.email")} name="email" type="email" autoComplete="email" required value={form.email} onChange={set("email")} error={serverErrors.email?.[0]} />
        <Field
          label={t("auth.dob")}
          name="date_of_birth"
          type="date"
          max={today}
          required
          value={form.date_of_birth}
          onChange={set("date_of_birth")}
          onBlur={() => setTouched(true)}
          error={fieldError("date_of_birth")}
        />
        {needsParent && (
          <Field
            label={t("auth.parentalEmail")}
            name="parental_email"
            type="email"
            hint={t("auth.parentalHint")}
            required
            value={form.parental_email}
            onChange={set("parental_email")}
            error={serverErrors.parental_email?.[0]}
          />
        )}
        <Field
          label={t("auth.password")}
          name="password"
          type="password"
          autoComplete="new-password"
          hint={t("auth.passwordMin")}
          required
          value={form.password}
          onChange={set("password")}
          onBlur={() => setTouched(true)}
          error={fieldError("password")}
        />
        <Field
          label={t("auth.passwordConfirm")}
          name="password_confirmation"
          type="password"
          autoComplete="new-password"
          required
          value={form.password_confirmation}
          onChange={set("password_confirmation")}
          onBlur={() => setTouched(true)}
          error={fieldError("password_confirmation")}
        />
        {error && <Alert tone="danger">{error}</Alert>}
        <p className="text-xs text-ink-2">{t("auth.terms")}</p>
        <Button type="submit" block loading={loading} disabled={!valid}>
          {t("auth.createAccount")}
        </Button>
      </form>
    </AuthLayout>
  );
}
