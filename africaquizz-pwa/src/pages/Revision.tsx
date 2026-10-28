import { useTranslation } from "react-i18next";
import { useQuery } from "@tanstack/react-query";
import { revisionApi } from "../lib/endpoints";
import { useAuth } from "../context/AuthContext";
import { useErrorMessage } from "../hooks/useCommon";
import { useStartGame } from "../hooks/useStartGame";
import { Alert, Button, ButtonLink, Card, PageTitle, Skeleton } from "../components/ui/primitives";

export default function Revision() {
  const { t } = useTranslation();
  const { isAuthenticated } = useAuth();
  const errMsg = useErrorMessage();
  const { start, loading, error } = useStartGame();
  const q = useQuery({ queryKey: ["me", "revision"], queryFn: revisionApi.queue, enabled: isAuthenticated, staleTime: 60_000 });

  return (
    <div className="space-y-5">
      <PageTitle sub={t("revision.intro")}>🔁 {t("revision.title")}</PageTitle>
      {!isAuthenticated ? (
        <Card className="space-y-4 text-center">
          <p className="font-semibold">{t("revision.loginRequired")}</p>
          <ButtonLink to="/connexion" block>
            {t("auth.login")}
          </ButtonLink>
        </Card>
      ) : q.isLoading ? (
        <Skeleton className="h-32" />
      ) : q.isError ? (
        <Alert tone="danger">{errMsg(q.error)}</Alert>
      ) : (
        <Card className="space-y-4 text-center">
          <p className="font-title text-5xl font-bold">{q.data?.due_count ?? 0}</p>
          <p className="font-semibold">{t("revision.due", { count: q.data?.due_count ?? 0 })}</p>
          {q.data && q.data.due_count > 0 ? (
            <Button block onClick={() => void start("revision")} loading={loading === "revision"}>
              {t("revision.start")} →
            </Button>
          ) : (
            <p className="text-sm text-ink-2">{t("revision.none")}</p>
          )}
        </Card>
      )}
      {error && <Alert tone="danger">{error}</Alert>}
    </div>
  );
}
