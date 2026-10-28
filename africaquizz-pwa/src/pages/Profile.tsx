import { useMutation, useQuery } from "@tanstack/react-query";
import { useTranslation } from "react-i18next";
import { useAuth } from "../context/AuthContext";
import { useSettings } from "../context/SettingsContext";
import { BADGES, levelProgress, nextRank } from "../lib/game";
import { authApi, countriesApi } from "../lib/endpoints";
import { useErrorMessage, useMyStats } from "../hooks/useCommon";
import { Alert, ButtonLink, Card, PageTitle, ProgressBar, Skeleton, cx } from "../components/ui/primitives";
import { AVATAR_COUNT, Avatar } from "../components/ui/Brand";

export default function Profile() {
  const { t, i18n } = useTranslation();
  const { user, isAuthenticated, refresh } = useAuth();
  const { settings, update } = useSettings();
  const stats = useMyStats();
  const errMsg = useErrorMessage();
  const countries = useQuery({
    queryKey: ["countries"],
    queryFn: countriesApi.list,
    enabled: isAuthenticated,
    staleTime: 60 * 60 * 1000,
  });
  const saveCountry = useMutation({
    mutationFn: (countryId: string | null) => authApi.updateProfile({ country_id: countryId }),
    onSuccess: () => refresh(),
  });
  const displayNames = new Intl.DisplayNames([i18n.resolvedLanguage ?? "fr"], { type: "region" });
  const sortedCountries = [...(countries.data ?? [])].sort((a, b) =>
    (displayNames.of(a.iso2) ?? a.iso2).localeCompare(displayNames.of(b.iso2) ?? b.iso2, i18n.resolvedLanguage),
  );

  if (!isAuthenticated) {
    return (
      <div className="space-y-5">
        <PageTitle>{t("profile.title")}</PageTitle>
        <Card className="space-y-4 text-center">
          <Avatar index={settings.avatar} size={88} className="mx-auto" />
          <h2 className="font-title text-2xl font-semibold">{t("profile.guestTitle")}</h2>
          <p className="text-ink-2">{t("profile.guestText")}</p>
          <ButtonLink to="/inscription" block>
            {t("auth.createAccount")}
          </ButtonLink>
          <ButtonLink to="/connexion" variant="secondary" block>
            {t("auth.login")}
          </ButtonLink>
        </Card>
      </div>
    );
  }

  const p = user?.profile;
  const xp = p?.total_xp ?? 0;
  const lp = levelProgress(xp);
  const nr = nextRank(xp);
  const pseudo = p?.pseudo ?? user?.name ?? "";
  const s = stats.data;
  const since = s?.createdAt ? new Intl.DateTimeFormat(i18n.language, { month: "long", year: "numeric" }).format(new Date(s.createdAt)) : null;

  return (
    <div className="space-y-5">
      <Card className="text-center">
        <Avatar index={settings.avatar} size={96} initial={pseudo} label={pseudo} className="mx-auto" />
        <h1 className="mt-3 font-title text-3xl font-bold">{pseudo}</h1>
        {p && <p className="font-bold text-brand-gold-dark">{t(`ranks.${p.rank}`)}</p>}
        {since && <p className="text-xs text-ink-2">{t("profile.memberSince", { date: since })}</p>}

        <div className="mt-5 text-left">
          <div className="flex items-end justify-between">
            <span className="font-title text-xl font-semibold">
              {t("profile.level")} {lp.level}
            </span>
            <span className="text-sm font-bold text-ink-2">{xp.toLocaleString()} XP</span>
          </div>
          <ProgressBar value={lp.pct} className="mt-2" label={t("profile.level")} />
          <p className="mt-2 text-xs text-ink-2">
            {nr ? t("profile.nextRank", { xp: (nr.minXp - xp).toLocaleString(), rank: t(`ranks.${nr.code}`) }) : t("profile.maxRank")}
          </p>
        </div>
      </Card>

      {stats.isError && <Alert tone="danger">{errMsg(stats.error)}</Alert>}

      {/* Série */}
      <section className="grid grid-cols-3 gap-3">
        {[
          { icon: "🔥", label: t("profile.streak"), value: s?.streak.current },
          { icon: "🏅", label: t("profile.longest"), value: s?.streak.longest },
          { icon: "🧊", label: t("profile.freezes"), value: s?.streak.freezes },
        ].map((x) => (
          <Card key={x.label} className="p-3 text-center">
            <p className="text-2xl" aria-hidden>
              {x.icon}
            </p>
            {stats.isLoading ? <Skeleton className="mx-auto mt-1 h-7 w-10" /> : <p className="font-title text-2xl font-bold">{x.value ?? 0}</p>}
            <p className="text-xs font-bold text-ink-2">{x.label}</p>
          </Card>
        ))}
      </section>

      {/* Statistiques */}
      <Card>
        <h2 className="font-title text-xl font-semibold">{t("profile.stats")}</h2>
        <dl className="mt-3 grid grid-cols-2 gap-3">
          {[
            { label: t("profile.games"), value: s?.games },
            { label: t("profile.accuracy"), value: s?.accuracy != null ? `${s.accuracy} %` : "–" },
            { label: t("profile.bestScore"), value: s?.bestScore },
            { label: t("profile.dailyPlayed"), value: s?.dailyPlayed },
          ].map((x) => (
            <div key={x.label} className="rounded-2xl bg-ink/5 p-3">
              <dt className="text-xs font-bold text-ink-2">{x.label}</dt>
              <dd className="font-title text-2xl font-bold">{stats.isLoading ? "…" : x.value ?? 0}</dd>
            </div>
          ))}
        </dl>
      </Card>

      <Card className="space-y-3">
        <div>
          <h2 className="font-title text-xl font-semibold">{t("profile.country")}</h2>
          <p className="text-sm text-ink-2">{t("profile.countryHelp")}</p>
        </div>
        <label htmlFor="profile-country" className="text-sm font-bold">
          {t("profile.country")}
        </label>
        <select
          id="profile-country"
          value={p?.country_id ?? ""}
          disabled={countries.isLoading || countries.isError || saveCountry.isPending}
          onChange={(event) => saveCountry.mutate(event.target.value || null)}
          className="w-full rounded-xl border border-line bg-white px-3 py-3 text-ink focus:outline-none focus:ring-2 focus:ring-brand-gold dark:bg-ink"
        >
          <option value="">{t("profile.countryPlaceholder")}</option>
          {sortedCountries.map((country) => (
            <option key={country.id} value={country.id}>
              {displayNames.of(country.iso2) ?? country.iso2}
            </option>
          ))}
        </select>
        {countries.isLoading && <p className="text-sm text-ink-2">{t("profile.countryLoading")}</p>}
        {countries.isError && <Alert tone="danger">{t("profile.countryLoadError")}</Alert>}
        {saveCountry.isPending && <p className="text-sm text-ink-2">{t("profile.countrySaving")}</p>}
        {saveCountry.isSuccess && <p className="text-sm text-brand-green">{t("profile.countrySaved")}</p>}
        {saveCountry.isError && <Alert tone="danger">{errMsg(saveCountry.error)}</Alert>}
      </Card>

      {/* Badges */}
      <Card>
        <h2 className="font-title text-xl font-semibold">
          {t("profile.badges")} <span className="text-base text-ink-2">({s?.badgeCount ?? 0}/{BADGES.length})</span>
        </h2>
        <ul className="mt-3 grid grid-cols-4 gap-3">
          {BADGES.map((b) => {
            const earned = s?.badgeCodes.includes(b.code);
            return (
              <li key={b.code} className="text-center">
                <span
                  className={cx(
                    "mx-auto grid h-14 w-14 place-items-center rounded-full border-2 text-2xl",
                    earned ? "border-black bg-brand-gold dark:border-brand-gold" : "border-dashed border-line bg-ink/5 grayscale",
                  )}
                  aria-hidden
                >
                  <span className={earned ? "" : "opacity-40"}>{b.icon}</span>
                </span>
                <span className={cx("mt-1 block text-[11px] font-bold leading-tight", !earned && "text-ink-2")}>{t(`badges.${b.code}`)}</span>
              </li>
            );
          })}
        </ul>
      </Card>

      {/* Avatar */}
      <Card>
        <h2 className="font-title text-xl font-semibold">{t("profile.avatar")}</h2>
        <p className="text-xs text-ink-2">{t("profile.avatarLocal")}</p>
        <div className="mt-3 grid grid-cols-4 gap-3" role="radiogroup" aria-label={t("profile.avatar")}>
          {Array.from({ length: AVATAR_COUNT }, (_, i) => (
            <button
              key={i}
              role="radio"
              aria-checked={settings.avatar === i}
              onClick={() => update({ avatar: i })}
              className={cx("mx-auto rounded-full p-1 transition-transform", settings.avatar === i ? "ring-4 ring-brand-gold" : "hover:scale-105")}
            >
              <Avatar index={i} size={56} label={`Avatar ${i + 1}`} />
            </button>
          ))}
        </div>
      </Card>
    </div>
  );
}
