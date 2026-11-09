import { useMemo, useState, type FormEvent } from "react";
import { Navigate, useLocation, useNavigate } from "react-router-dom";
import { useTranslation } from "react-i18next";
import { useQuery } from "@tanstack/react-query";
import { useAuth } from "../../context/AuthContext";
import { useSettings } from "../../context/SettingsContext";
import { normalizeError } from "../../lib/api";
import { countriesApi } from "../../lib/endpoints";
import { ageFromDob } from "../../lib/game";
import { useErrorMessage } from "../../hooks/useCommon";
import type { GoogleCompleteState } from "../../hooks/useGoogleAuth";
import { Alert, Button, Field } from "../../components/ui/primitives";
import { CountryPicker } from "../../components/ui/CountryPicker";
import AuthLayout from "./AuthLayout";

// Dernière étape d'une inscription Google : l'âge (13 ans minimum) et le pays restent obligatoires.
export default function GoogleComplete() {
  const { t } = useTranslation();
  const { googleSignIn } = useAuth();
  const { settings } = useSettings();
  const navigate = useNavigate();
  const errMsg = useErrorMessage();
  const google = useLocation().state as GoogleCompleteState | null;
  const [dob, setDob] = useState("");
  const [parentalEmail, setParentalEmail] = useState("");
  const [countryId, setCountryId] = useState<string | null>(null);
  const countries = useQuery({ queryKey: ["countries"], queryFn: countriesApi.list, staleTime: 60 * 60 * 1000 });
  const [touched, setTouched] = useState(false);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [serverErrors, setServerErrors] = useState<Record<string, string[]>>({});

  const age = useMemo(() => ageFromDob(dob), [dob]);
  const needsParent = age >= 13 && age <= 15;
  const tooYoung = Number.isFinite(age) && age < 13;
  const valid = !!countryId && Number.isFinite(age) && age >= 13 && (!needsParent || !!parentalEmail.trim());

  // Accès direct sans passer par le bouton Google : retour à l'inscription
  if (!google?.credential) return <Navigate to="/inscription" replace />;

  async function onSubmit(e: FormEvent) {
    e.preventDefault();
    setTouched(true);
    if (!valid || !google) return;
    setLoading(true);
    setError(null);
    try {
      const res = await googleSignIn({
        credential: google.credential,
        date_of_birth: dob,
        country_id: countryId!,
        timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
        locale: settings.locale,
        ...(needsParent ? { parental_email: parentalEmail.trim() } : {}),
      });
      if (res.status === "signed_in") {
        navigate("/", { replace: true, state: { welcome: true, parental: res.requiresParental } });
      }
    } catch (err) {
      const n = normalizeError(err);
      if (n.fieldErrors) setServerErrors(n.fieldErrors);
      // L'ID token Google expire au bout d'une heure : il faut alors relancer le bouton
      if (n.status === 401) setError(t("auth.googleExpired"));
      else setError(n.status === 403 ? t("auth.tooYoung") : errMsg(err));
    } finally {
      setLoading(false);
    }
  }

  const today = new Date().toISOString().slice(0, 10);

  return (
    <AuthLayout title={t("auth.googleCompleteTitle")} intro={t("auth.googleCompleteIntro", { email: google.email })}>
      <form onSubmit={onSubmit} className="space-y-4" noValidate>
        <Field
          label={t("auth.dob")}
          name="date_of_birth"
          type="date"
          max={today}
          required
          value={dob}
          onChange={(e) => setDob(e.target.value)}
          onBlur={() => setTouched(true)}
          error={(touched && tooYoung ? t("auth.tooYoung") : undefined) ?? serverErrors.date_of_birth?.[0]}
        />
        {countries.isError ? (
          <Alert tone="danger">{t("profile.countryLoadError")}</Alert>
        ) : (
          <CountryPicker
            label={t("auth.country")}
            hint={t("auth.countryHint")}
            countries={countries.data ?? []}
            value={countryId}
            disabled={countries.isLoading}
            onChange={setCountryId}
            error={serverErrors.country_id?.[0]}
          />
        )}
        {needsParent && (
          <Field
            label={t("auth.parentalEmail")}
            name="parental_email"
            type="email"
            hint={t("auth.parentalHint")}
            required
            value={parentalEmail}
            onChange={(e) => setParentalEmail(e.target.value)}
            error={serverErrors.parental_email?.[0]}
          />
        )}
        {error && <Alert tone="danger">{error}</Alert>}
        <p className="text-xs text-ink-2">{t("auth.terms")}</p>
        <Button type="submit" block loading={loading} disabled={!valid}>
          {t("auth.createAccount")}
        </Button>
      </form>
    </AuthLayout>
  );
}
