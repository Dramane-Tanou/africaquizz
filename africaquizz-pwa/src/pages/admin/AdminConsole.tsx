import { useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Navigate, useParams } from "react-router-dom";
import { useTranslation } from "react-i18next";
import { Alert, Button, Card, FullPageSpinner, PageTitle } from "../../components/ui/primitives";
import { ADMIN_TOKEN_KEY, adminApi, adminErrorMessage } from "../../lib/adminApi";
import AdminRoleSections from "./AdminRoleSections";
import { adminPathForRole, adminRoleForPath, adminRoleTitle } from "./adminRoles";

type Admin = {
  id: string;
  name: string;
  email: string;
  role: { name: string; label_fr: string; label_en: string };
};
type Translation = { locale: string; text: string };
type Question = { id: string; status: string; difficulty: string; translations: Translation[] };
type QuestionDetails = {
  translations: Translation[];
  options?: Array<{ option_key: string; translations: Translation[] }>;
  sources?: Array<{ title: string; url: string; publisher?: string; source_type?: string }>;
};
type Report = {
  id: string;
  reason_label: string;
  comment: string | null;
  reporter: { pseudo?: string; guest?: boolean };
  question_excerpt: string | null;
};
type Overview = {
  users: { total: number; verified: number; banned: number };
  questions: { total: number; pending_review: number; flagged: number };
  content: { categories: number; countries: number };
  moderation: { reports_pending: number; reports_resolved: number };
  system: { admins_total: number; audit_logs_total: number };
};

const checklistKeys = [
  "fact_exact", "sources_ok", "single_correct_answer", "distractors_plausible", "statement_clear",
  "explanation_concise", "neutral_respectful", "difficulty_consistent", "english_reviewed", "image_licensed",
] as const;

const statuses: Record<string, string> = {
  BROUILLON: "admin.statusDraft",
  A_VERIFIER: "admin.statusReview",
  VALIDEE: "admin.statusApproved",
  PUBLIEE: "admin.statusPublished",
  A_REVISER: "admin.statusNeedsReview",
  ARCHIVEE: "admin.statusArchived",
};

export default function AdminConsole() {
  const { t, i18n } = useTranslation();
  const { role: rolePath } = useParams();
  const queryClient = useQueryClient();
  const token = sessionStorage.getItem(ADMIN_TOKEN_KEY);
  const [logoutPending, setLogoutPending] = useState(false);
  const [activeQuestion, setActiveQuestion] = useState<Question | null>(null);
  const [checklist, setChecklist] = useState<Record<(typeof checklistKeys)[number], boolean>>(
    Object.fromEntries(checklistKeys.map((key) => [key, false])) as Record<(typeof checklistKeys)[number], boolean>,
  );
  const [actionError, setActionError] = useState("");
  const [selectedQuestionId, setSelectedQuestionId] = useState<string | null>(null);

  const me = useQuery({
    queryKey: ["admin", "me"],
    queryFn: () => adminApi.get<{ data: Admin }>("/auth/me").then((response) => response.data.data),
    enabled: !!token,
    retry: false,
  });
  const role = me.data?.role.name ?? "";
  const canValidate = ["relecteur", "admin", "super_admin"].includes(role);
  const canPublish = ["editeur", "admin", "super_admin"].includes(role);
  const canCreate = ["redacteur", "editeur", "admin", "super_admin"].includes(role);
  const canManage = ["admin", "super_admin"].includes(role);
  const canModerate = ["relecteur", "admin", "super_admin"].includes(role);
  const roleDefinition = adminRoleForPath(rolePath);

  const questionDetails = useQuery({
    queryKey: ["admin", "question", selectedQuestionId],
    queryFn: () => adminApi.get<{ data: QuestionDetails }>(`/questions/${selectedQuestionId}`).then((response) => response.data.data),
    enabled: !!selectedQuestionId,
  });

  const overview = useQuery({
    queryKey: ["admin", "overview"],
    queryFn: () => adminApi.get<{ data: Overview }>("/super/dashboard/overview").then((response) => response.data.data),
    enabled: !!me.data && canManage,
  });
  const questions = useQuery({
    queryKey: ["admin", "questions"],
    queryFn: () => {
      const status = role === "relecteur" ? "A_VERIFIER,A_REVISER"
        : role === "editeur" ? "BROUILLON,VALIDEE,PUBLIEE,A_REVISER,ARCHIVEE"
          : undefined;
      return adminApi.get<{ data: { data: Question[] } }>("/questions", { params: status ? { status } : undefined })
        .then((response) => response.data.data.data);
    },
    enabled: !!me.data,
  });
  const reports = useQuery({
    queryKey: ["admin", "reports"],
    queryFn: () => adminApi.get<{ data: { data: Report[] } }>("/reports").then((response) => response.data.data.data),
    enabled: !!me.data && canModerate,
  });

  const transition = useMutation({
    mutationFn: ({ id, target_status, approvedChecklist }: {
      id: string;
      target_status: string;
      approvedChecklist?: Record<string, boolean>;
    }) => adminApi.post(`/questions/${id}/transition`, {
      target_status,
      checklist: approvedChecklist,
    }),
    onSuccess: async () => {
      setActiveQuestion(null);
      setActionError("");
      await queryClient.invalidateQueries({ queryKey: ["admin"] });
    },
    onError: (error) => setActionError(adminErrorMessage(error, t("admin.actionError"))),
  });
  const resolveReport = useMutation({
    mutationFn: ({ id, decision }: { id: string; decision: "resolved" | "dismissed" }) =>
      adminApi.post(`/reports/${id}/resolve`, { decision }),
    onSuccess: async () => {
      setActionError("");
      await queryClient.invalidateQueries({ queryKey: ["admin", "reports"] });
      await queryClient.invalidateQueries({ queryKey: ["admin", "overview"] });
    },
    onError: (error) => setActionError(adminErrorMessage(error, t("admin.actionError"))),
  });

  if (!token) return <Navigate to="/admin/connexion" replace />;
  if (me.isLoading) return <main className="p-8"><FullPageSpinner /></main>;
  if (me.isError) {
    sessionStorage.removeItem(ADMIN_TOKEN_KEY);
    return <Navigate to="/admin/connexion" replace />;
  }
  if (!me.data) return <main className="p-8"><FullPageSpinner /></main>;
  if (!roleDefinition || roleDefinition !== role) {
    return <Navigate to={adminPathForRole(role)} replace />;
  }

  const logout = async () => {
    setLogoutPending(true);
    try {
      await adminApi.post("/auth/logout");
    } catch (error) {
      setActionError(adminErrorMessage(error, t("admin.logoutError")));
    } finally {
      sessionStorage.removeItem(ADMIN_TOKEN_KEY);
      await queryClient.removeQueries({ queryKey: ["admin"] });
      setLogoutPending(false);
    }
  };
  const displayQuestionText = (question: Question) =>
    question.translations.find((item) => item.locale === (i18n.resolvedLanguage ?? "fr"))?.text
      ?? question.translations[0]?.text
      ?? t("admin.questionWithoutText");
  const actionsFor = (question: Question) => {
    if (question.status === "BROUILLON" && canCreate) return [{ status: "A_VERIFIER", label: t("admin.submitReview") }];
    if (question.status === "A_VERIFIER" && canValidate) {
      return [
        { status: "VALIDEE", label: t("admin.approve") },
        { status: "BROUILLON", label: t("admin.reject") },
      ];
    }
    if (question.status === "VALIDEE" && canPublish) return [{ status: "PUBLIEE", label: t("admin.publish") }];
    if (question.status === "PUBLIEE" && canPublish) {
      return [
        { status: "A_REVISER", label: t("admin.flagForReview") },
        { status: "ARCHIVEE", label: t("admin.archive") },
      ];
    }
    if (question.status === "A_REVISER") {
      return [
        ...(canValidate ? [{ status: "VALIDEE", label: t("admin.approve") }] : []),
        ...(canPublish ? [{ status: "ARCHIVEE", label: t("admin.archive") }] : []),
      ];
    }
    return [];
  };
  const beginTransition = (question: Question, targetStatus: string) => {
    setActionError("");
    if (targetStatus === "VALIDEE") {
      setChecklist(Object.fromEntries(checklistKeys.map((key) => [key, false])) as Record<(typeof checklistKeys)[number], boolean>);
      setActiveQuestion(question);
      return;
    }
    transition.mutate({ id: question.id, target_status: targetStatus });
  };
  const confirmValidation = () => {
    if (!activeQuestion || !checklistKeys.every((key) => checklist[key])) return;
    transition.mutate({ id: activeQuestion.id, target_status: "VALIDEE", approvedChecklist: checklist });
  };

  return (
    <main className="min-h-[100dvh] bg-bg px-4 py-6 text-ink">
      <div className="mx-auto max-w-5xl space-y-6">
        <header className="flex flex-wrap items-start justify-between gap-4">
          <div>
            <p className="text-sm font-bold uppercase tracking-wide text-brand-green">{t("admin.console")}</p>
            <PageTitle sub={`${me.data.name} · ${me.data.email}`}>
              {i18n.resolvedLanguage === "en" ? me.data.role.label_en : me.data.role.label_fr}
            </PageTitle>
          </div>
          <Button variant="secondary" loading={logoutPending} onClick={() => void logout()}>{t("admin.logout")}</Button>
        </header>

        {actionError && <Alert tone="danger">{actionError}</Alert>}
        <Card className="space-y-2">
          <h2 className="font-title text-lg font-bold">{adminRoleTitle(roleDefinition, t)}</h2>
          <p className="text-sm text-ink-2">{t(`admin.workspace.${roleDefinition}`)}</p>
        </Card>

        {overview.isError && <Alert tone="warning">{t("admin.overviewUnavailable")}</Alert>}

        {overview.data && (
          <section aria-label={t("admin.overview")} className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            {[
              [t("admin.players"), overview.data.users.total],
              [t("admin.questions"), overview.data.questions.total],
              [t("admin.toReview"), overview.data.questions.pending_review],
              [t("admin.pendingReports"), overview.data.moderation.reports_pending],
            ].map(([label, value]) => (
              <Card key={String(label)} className="space-y-1">
                <p className="text-sm font-bold text-ink-2">{label}</p>
                <p className="font-title text-3xl font-bold">{value}</p>
              </Card>
            ))}
          </section>
        )}

        <section className="grid gap-4 lg:grid-cols-2">
          <Card className="space-y-4">
            <div className="flex items-center justify-between gap-2">
              <h2 className="font-title text-xl font-bold">{t("admin.questionQueue")}</h2>
              {questions.data && <span className="rounded-full bg-ink/5 px-3 py-1 text-sm font-bold">{questions.data.length}</span>}
            </div>
            {questions.isLoading && <p className="text-sm text-ink-2">{t("admin.loading")}</p>}
            {questions.isError && <Alert tone="danger">{adminErrorMessage(questions.error, t("admin.loadError"))}</Alert>}
            {questions.data?.length === 0 && <p className="text-sm text-ink-2">{t("admin.noQuestions")}</p>}
            <ul className="space-y-3">
              {questions.data?.map((question) => (
                <li key={question.id} className="rounded-2xl border border-line p-3">
                  <p className="font-semibold">{displayQuestionText(question)}</p>
                  <div className="mt-2 grid gap-2 sm:grid-cols-2">
                    {question.translations.map((translation) => (
                      <p key={translation.locale} className="rounded-lg bg-ink/5 p-2 text-sm">
                        <strong>{translation.locale.toUpperCase()}</strong> · {translation.text}
                      </p>
                    ))}
                  </div>
                  <div className="mt-2 flex flex-wrap items-center gap-2">
                    <span className="rounded-full bg-ink/5 px-2 py-1 text-xs">{t(statuses[question.status] ?? "admin.statusUnknown")}</span>
                    <span className="text-xs text-ink-2">{question.difficulty}</span>
                  </div>
                  <Button
                    className="mt-2 min-h-9 px-3 py-1 text-xs"
                    variant="secondary"
                    onClick={() => setSelectedQuestionId(selectedQuestionId === question.id ? null : question.id)}
                  >{selectedQuestionId === question.id ? t("admin.hideDetails") : t("admin.showSourcesAndOptions")}</Button>
                  {selectedQuestionId === question.id && (
                    <div className="mt-3 rounded-xl bg-ink/5 p-3 text-sm">
                      {questionDetails.isLoading && <p>{t("admin.loading")}</p>}
                      {questionDetails.isError && <Alert tone="danger">{adminErrorMessage(questionDetails.error, t("admin.loadError"))}</Alert>}
                      {questionDetails.data?.options?.map((option) => (
                        <div key={option.option_key} className="py-1">
                          <strong>{option.option_key}</strong>
                          {option.translations.map((item) => <span key={item.locale}> · {item.locale.toUpperCase()}: {item.text}</span>)}
                        </div>
                      ))}
                      {questionDetails.data?.sources?.map((source) => (
                        <p key={source.url} className="pt-2">
                          {source.title} · <a className="underline" href={source.url} target="_blank" rel="noreferrer">{source.publisher ?? source.url}</a>
                        </p>
                      ))}
                      {questionDetails.data && !questionDetails.data.sources?.length && <p className="pt-2">{t("admin.noSources")}</p>}
                    </div>
                  )}
                  {actionsFor(question).length > 0 && (
                    <div className="mt-3 flex flex-wrap gap-2">
                      {actionsFor(question).map((action) => (
                        <Button
                          key={action.status}
                          className="min-h-10 px-3 py-2 text-sm"
                          variant={action.status === "BROUILLON" || action.status === "ARCHIVEE" ? "secondary" : "primary"}
                          loading={transition.isPending}
                          onClick={() => beginTransition(question, action.status)}
                        >
                          {action.label}
                        </Button>
                      ))}
                    </div>
                  )}
                </li>
              ))}
            </ul>
          </Card>

          {canModerate && <Card className="space-y-4">
            <div className="flex items-center justify-between gap-2">
              <h2 className="font-title text-xl font-bold">{t("admin.reportQueue")}</h2>
              {reports.data && <span className="rounded-full bg-ink/5 px-3 py-1 text-sm font-bold">{reports.data.length}</span>}
            </div>
            {reports.isLoading && <p className="text-sm text-ink-2">{t("admin.loading")}</p>}
            {reports.isError && <Alert tone="danger">{adminErrorMessage(reports.error, t("admin.loadError"))}</Alert>}
            {reports.data?.length === 0 && <p className="text-sm text-ink-2">{t("admin.noReports")}</p>}
            <ul className="space-y-3">
              {reports.data?.map((report) => (
                <li key={report.id} className="rounded-2xl border border-line p-3">
                  <p className="text-xs font-bold uppercase text-brand-green">{report.reason_label}</p>
                  <p className="mt-1 font-semibold">{report.question_excerpt ?? t("admin.questionWithoutText")}</p>
                  {report.comment && <p className="mt-1 text-sm text-ink-2">{report.comment}</p>}
                  <p className="mt-2 text-xs text-ink-2">{report.reporter.pseudo ?? t("admin.guestReporter")}</p>
                  <div className="mt-3 flex gap-2">
                    <Button className="min-h-10 px-3 py-2 text-sm" loading={resolveReport.isPending} onClick={() => resolveReport.mutate({ id: report.id, decision: "resolved" })}>{t("admin.confirmReport")}</Button>
                    <Button className="min-h-10 px-3 py-2 text-sm" variant="secondary" loading={resolveReport.isPending} onClick={() => resolveReport.mutate({ id: report.id, decision: "dismissed" })}>{t("admin.dismissReport")}</Button>
                  </div>
                </li>
              ))}
            </ul>
          </Card>}
        </section>

        <AdminRoleSections role={roleDefinition} />
      </div>

      {activeQuestion && (
        <div className="fixed inset-0 z-50 overflow-y-auto bg-black/60 p-4" role="presentation">
          <section role="dialog" aria-modal="true" aria-labelledby="review-title" className="mx-auto my-6 max-w-xl rounded-3xl border-2 border-black bg-surface p-5 shadow-flat">
            <h2 id="review-title" className="font-title text-2xl font-bold">{t("admin.reviewChecklist")}</h2>
            <p className="mt-2 text-sm text-ink-2">{displayQuestionText(activeQuestion)}</p>
            <ul className="mt-4 space-y-2">
              {checklistKeys.map((key) => (
                <li key={key}>
                  <label className="flex items-start gap-3 rounded-xl bg-ink/5 p-3 text-sm">
                    <input
                      type="checkbox"
                      checked={checklist[key]}
                      onChange={(event) => setChecklist((current) => ({ ...current, [key]: event.target.checked }))}
                      className="mt-1 accent-brand-green"
                    />
                    {t(`admin.checklist.${key}`)}
                  </label>
                </li>
              ))}
            </ul>
            <div className="mt-4 flex flex-wrap justify-end gap-2">
              <Button variant="secondary" onClick={() => setActiveQuestion(null)}>{t("app.cancel")}</Button>
              <Button disabled={!checklistKeys.every((key) => checklist[key])} loading={transition.isPending} onClick={confirmValidation}>
                {t("admin.approve")}
              </Button>
            </div>
          </section>
        </div>
      )}
    </main>
  );
}
