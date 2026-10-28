import { useTranslation } from "react-i18next";
import { useQuery } from "@tanstack/react-query";
import { dailyApi } from "../lib/endpoints";
import { useAuth } from "../context/AuthContext";
import { useErrorMessage } from "../hooks/useCommon";
import { useStartGame } from "../hooks/useStartGame";
import { Alert, Button, ButtonLink, Card, PageTitle, Skeleton } from "../components/ui/primitives";

export default function DailyChallenge() {
  const { t, i18n } = useTranslation();
  const { isAuthenticated } = useAuth();
  const errMsg = useErrorMessage();
  const { start, loading, error } = useStartGame();
  const q = useQuery({ queryKey: ["daily", isAuthenticated], queryFn: dailyApi.status, staleTime: 60_000 });
  const d = q.data;
  const closes = d?.closes_at
    ? new Intl.DateTimeFormat(i18n.language, { dateStyle: "medium", timeStyle: "short" }).format(new Date(d.closes_at))
    : null;

  return (
    <div className="space-y-5">
      <PageTitle sub={t("daily.rules")}>📅 {t("daily.title")}</PageTitle>
      {q.isLoading && <Skeleton className="h-40" />}
      {q.isError && (
        <Alert tone="danger" action={<Button variant="secondary" className="min-h-[40px] px-3 py-1 text-sm" onClick={() => void q.refetch()}>{t("app.retry")}</Button>}>
          {errMsg(q.error)}
        </Alert>
      )}
      {d && (
        <Card className="space-y-4">
          {d.status === "available" && (
            <>
              <p className="font-title text-2xl font-semibold">{t("home.dailyAvailable")}</p>
              {closes && <p className="text-sm text-ink-2">{t("daily.closesAt", { date: closes })}</p>}
              {!isAuthenticated && <Alert tone="info">{t("daily.loginHint")}</Alert>}
              <Button block onClick={() => void start("daily")} loading={loading === "daily"}>
                {t("daily.start")} →
              </Button>
            </>
          )}
          {d.status === "played" && (
            <>
              <p className="font-semibold">{t("daily.already")}</p>
              {d.my_result && (
                <div className="grid grid-cols-2 gap-3 text-center">
                  <div className="rounded-2xl bg-brand-gold/20 p-4">
                    <p className="font-title text-3xl font-bold">{d.my_result.correct}/10</p>
                    <p className="text-xs font-bold text-ink-2">{t("result.correct")}</p>
                  </div>
                  <div className="rounded-2xl bg-brand-green/20 p-4">
                    <p className="font-title text-3xl font-bold">{d.my_result.score}</p>
                    <p className="text-xs font-bold text-ink-2">{t("result.score")}</p>
                  </div>
                </div>
              )}
            </>
          )}
          {d.status === "closed" && <p className="font-semibold">{t("home.dailyClosed")}</p>}
          {d.status === "not_yet" && <p className="font-semibold">{t("home.dailyNotYet")}</p>}
          {d.status !== "not_yet" && (
            <ButtonLink to="/classements?scope=daily" variant="secondary" block>
              {t("home.seeRanking")}
            </ButtonLink>
          )}
        </Card>
      )}
      {error && <Alert tone="danger">{error}</Alert>}
    </div>
  );
}
