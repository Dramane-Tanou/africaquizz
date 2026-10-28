import { useState, type FormEvent } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { useTranslation } from "react-i18next";
import { Alert, Button, Card, Field } from "../../components/ui/primitives";
import { adminApi, adminErrorMessage } from "../../lib/adminApi";
import type { AdminRole } from "./adminRoles";

type Props = { role: AdminRole };
type Player = {
  id: string;
  name: string;
  email: string;
  pseudo: string | null;
  is_banned: boolean;
  email_verified: boolean;
};
type Staff = {
  id: string;
  name: string;
  email: string;
  role: { name: string; label_fr: string; label_en: string };
};
type ImportJob = {
  id: string;
  original_filename: string;
  status: string;
  totals: { total?: number; valides?: number; erreurs?: number; avertissements?: number } | null;
  created_at: string;
};
type ImportDetails = {
  preview: Array<{ row_number: number; status: string; errors: string[] | null }>;
  error_message: string | null;
};
type MediaItem = { id: string; filename: string; mime_type: string; license: string | null; author: string | null };
type AuditEntry = { id: string; action: string; entity_type: string; entity_id: string; created_at: string };
type Category = { id: string; slug: string; questions_count: number };
type Badge = { id: string; code: string; name_fr: string; name_en: string; tier: string };

const staffRoles: Array<{ value: string; labelKey: string }> = [
  { value: "relecteur", labelKey: "admin.roleReviewer" },
  { value: "editeur", labelKey: "admin.roleEditor" },
  { value: "admin", labelKey: "admin.roleAdmin" },
  { value: "super_admin", labelKey: "admin.roleSuper" },
];

export default function AdminRoleSections({ role }: Props) {
  const { t, i18n } = useTranslation();
  const queryClient = useQueryClient();
  const isSuperAdmin = role === "super_admin";
  const canManageUsers = isSuperAdmin || role === "admin";
  const canManageContent = isSuperAdmin || role === "admin" || role === "editeur";
  const [banReason, setBanReason] = useState("");
  const [staffForm, setStaffForm] = useState({ name: "", email: "", password: "", role: "relecteur" });
  const [categorySlug, setCategorySlug] = useState("");
  const [importFile, setImportFile] = useState<File | null>(null);
  const [selectedImportId, setSelectedImportId] = useState<string | null>(null);
  const [mediaFile, setMediaFile] = useState<File | null>(null);
  const [mediaLicense, setMediaLicense] = useState("");
  const [mediaAuthor, setMediaAuthor] = useState("");
  const [mediaSource, setMediaSource] = useState("");
  const [mediaAltFr, setMediaAltFr] = useState("");
  const [mediaAltEn, setMediaAltEn] = useState("");
  const [sectionError, setSectionError] = useState("");
  const [sectionNotice, setSectionNotice] = useState("");

  const users = useQuery({
    queryKey: ["admin", "players"],
    queryFn: () => adminApi.get<{ data: { data: Player[] } }>("/super/users").then((response) => response.data.data.data),
    enabled: canManageUsers,
  });
  const staff = useQuery({
    queryKey: ["admin", "staff"],
    queryFn: () => adminApi.get<{ data: Staff[] }>("/super/admins").then((response) => response.data.data),
    enabled: isSuperAdmin,
  });
  const categories = useQuery({
    queryKey: ["admin", "categories"],
    queryFn: () => adminApi.get<{ data: Category[] }>("/super/categories").then((response) => response.data.data),
    enabled: isSuperAdmin,
  });
  const badges = useQuery({
    queryKey: ["admin", "badges"],
    queryFn: () => adminApi.get<{ data: Badge[] }>("/super/badges").then((response) => response.data.data),
    enabled: isSuperAdmin,
  });
  const audit = useQuery({
    queryKey: ["admin", "audit"],
    queryFn: () => adminApi.get<{ data: { data: AuditEntry[] } }>("/audit-logs").then((response) => response.data.data.data),
    enabled: canManageUsers,
  });
  const imports = useQuery({
    queryKey: ["admin", "imports"],
    queryFn: () => adminApi.get<{ data: ImportJob[] }>("/imports").then((response) => response.data.data),
    enabled: canManageContent,
  });
  const importDetails = useQuery({
    queryKey: ["admin", "import", selectedImportId],
    queryFn: () => adminApi.get<{ data: ImportDetails }>(`/imports/${selectedImportId}`).then((response) => response.data.data),
    enabled: !!selectedImportId,
  });
  const media = useQuery({
    queryKey: ["admin", "media"],
    queryFn: () => adminApi.get<{ data: { data: MediaItem[] } }>("/media").then((response) => response.data.data.data),
    enabled: canManageContent,
  });

  const refreshAdminData = async () => {
    await queryClient.invalidateQueries({ queryKey: ["admin"] });
  };
  const userAction = useMutation({
    mutationFn: ({ id, action }: { id: string; action: "ban" | "unban" | "verify" }) => {
      if (action === "ban") return adminApi.post(`/super/users/${id}/ban`, { reason: banReason });
      if (action === "unban") return adminApi.post(`/super/users/${id}/unban`);
      return adminApi.post(`/super/users/${id}/force-verify`);
    },
    onSuccess: refreshAdminData,
    onError: (error) => setSectionError(adminErrorMessage(error, t("admin.actionError"))),
  });
  const createStaff = useMutation({
    mutationFn: () => adminApi.post("/super/admins", staffForm),
    onSuccess: async () => {
      setStaffForm({ name: "", email: "", password: "", role: "relecteur" });
      setSectionError("");
      setSectionNotice(t("admin.staffCreated"));
      await refreshAdminData();
    },
    onError: (error) => setSectionError(adminErrorMessage(error, t("admin.actionError"))),
  });
  const deleteStaff = useMutation({
    mutationFn: (id: string) => adminApi.delete(`/super/admins/${id}`),
    onSuccess: refreshAdminData,
    onError: (error) => setSectionError(adminErrorMessage(error, t("admin.actionError"))),
  });
  const createCategory = useMutation({
    mutationFn: () => adminApi.post("/super/categories", { slug: categorySlug }),
    onSuccess: async () => {
      setCategorySlug("");
      setSectionError("");
      setSectionNotice(t("admin.categoryCreated"));
      await refreshAdminData();
    },
    onError: (error) => setSectionError(adminErrorMessage(error, t("admin.actionError"))),
  });
  const uploadImport = useMutation({
    mutationFn: () => {
      if (!importFile) throw new Error(t("admin.selectImport"));
      const body = new FormData();
      body.append("file", importFile);
      return adminApi.post("/imports", body, { headers: { "Content-Type": "multipart/form-data" } });
    },
    onSuccess: async () => {
      setImportFile(null);
      setSectionError("");
      setSectionNotice(t("admin.importUploaded"));
      await refreshAdminData();
    },
    onError: (error) => setSectionError(adminErrorMessage(error, t("admin.actionError"))),
  });
  const confirmImport = useMutation({
    mutationFn: (id: string) => adminApi.post(`/imports/${id}/confirm`),
    onSuccess: async () => {
      setSectionError("");
      setSectionNotice(t("admin.importConfirmed"));
      await refreshAdminData();
    },
    onError: (error) => setSectionError(adminErrorMessage(error, t("admin.actionError"))),
  });
  const cancelImport = useMutation({
    mutationFn: (id: string) => adminApi.delete(`/imports/${id}`),
    onSuccess: refreshAdminData,
    onError: (error) => setSectionError(adminErrorMessage(error, t("admin.actionError"))),
  });
  const uploadMedia = useMutation({
    mutationFn: () => {
      if (!mediaFile) throw new Error(t("admin.selectMedia"));
      const body = new FormData();
      body.append("file", mediaFile);
      body.append("license", mediaLicense);
      body.append("author", mediaAuthor);
      body.append("source_url", mediaSource);
      body.append("alt_text_fr", mediaAltFr);
      body.append("alt_text_en", mediaAltEn);
      return adminApi.post("/media/upload", body, { headers: { "Content-Type": "multipart/form-data" } });
    },
    onSuccess: async () => {
      setMediaFile(null);
      setMediaLicense("");
      setMediaAuthor("");
      setMediaSource("");
      setMediaAltFr("");
      setMediaAltEn("");
      setSectionError("");
      setSectionNotice(t("admin.mediaUploaded"));
      await refreshAdminData();
    },
    onError: (error) => setSectionError(adminErrorMessage(error, t("admin.actionError"))),
  });

  const submitStaff = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setSectionError("");
    setSectionNotice("");
    createStaff.mutate();
  };
  const submitCategory = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setSectionError("");
    setSectionNotice("");
    createCategory.mutate();
  };
  const submitImport = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setSectionError("");
    setSectionNotice("");
    uploadImport.mutate();
  };
  const submitMedia = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setSectionError("");
    setSectionNotice("");
    uploadMedia.mutate();
  };

  return (
    <div className="space-y-5">
      {sectionError && <Alert tone="danger">{sectionError}</Alert>}
      {sectionNotice && <Alert tone="success">{sectionNotice}</Alert>}

      {canManageUsers && (
        <Card className="space-y-4">
          <div>
            <h2 className="font-title text-xl font-bold">{t("admin.playerManagement")}</h2>
            <p className="text-sm text-ink-2">{t("admin.playerManagementHint")}</p>
          </div>
          <Field label={t("admin.banReason")} name="banReason" value={banReason} onChange={(event) => setBanReason(event.target.value)} />
          {users.isLoading && <p>{t("admin.loading")}</p>}
          {users.isError && <Alert tone="danger">{adminErrorMessage(users.error, t("admin.loadError"))}</Alert>}
          <div className="overflow-x-auto">
            <table className="w-full min-w-[640px] text-left text-sm">
              <thead><tr><th className="p-2">{t("admin.player")}</th><th className="p-2">{t("admin.status")}</th><th className="p-2">{t("admin.actions")}</th></tr></thead>
              <tbody>
                {users.data?.map((player) => (
                  <tr key={player.id} className="border-t border-line">
                    <td className="p-2"><strong>{player.pseudo || player.name}</strong><br />{player.email}</td>
                    <td className="p-2">{player.is_banned ? t("admin.banned") : t("admin.active")} · {player.email_verified ? t("admin.verified") : t("admin.unverified")}</td>
                    <td className="p-2">
                      <div className="flex flex-wrap gap-2">
                        <Button className="min-h-9 px-3 py-1 text-xs" variant="secondary" disabled={userAction.isPending || (!player.is_banned && (!player.email_verified || !banReason.trim()))} onClick={() => userAction.mutate({ id: player.id, action: player.is_banned ? "unban" : "ban" })}>
                          {player.is_banned ? t("admin.unban") : t("admin.ban")}
                        </Button>
                        {!player.email_verified && <Button className="min-h-9 px-3 py-1 text-xs" disabled={userAction.isPending} onClick={() => userAction.mutate({ id: player.id, action: "verify" })}>{t("admin.forceVerify")}</Button>}
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </Card>
      )}

      {isSuperAdmin && (
        <Card className="space-y-4">
          <div>
            <h2 className="font-title text-xl font-bold">{t("admin.staffManagement")}</h2>
            <p className="text-sm text-ink-2">{t("admin.staffManagementHint")}</p>
          </div>
          <form className="grid gap-3 sm:grid-cols-2" onSubmit={submitStaff}>
            <Field label={t("admin.staffName")} name="staffName" required value={staffForm.name} onChange={(event) => setStaffForm({ ...staffForm, name: event.target.value })} />
            <Field label={t("admin.email")} name="staffEmail" type="email" required value={staffForm.email} onChange={(event) => setStaffForm({ ...staffForm, email: event.target.value })} />
            <Field label={t("admin.password")} name="staffPassword" type="password" minLength={8} required value={staffForm.password} onChange={(event) => setStaffForm({ ...staffForm, password: event.target.value })} />
            <label className="grid gap-1 text-sm font-bold">{t("admin.role")}
              <select className="min-h-11 rounded-xl border border-line bg-surface px-3" value={staffForm.role} onChange={(event) => setStaffForm({ ...staffForm, role: event.target.value })}>
                {staffRoles.map((item) => <option key={item.value} value={item.value}>{t(item.labelKey)}</option>)}
              </select>
            </label>
            <Button className="sm:col-span-2" loading={createStaff.isPending}>{t("admin.createStaff")}</Button>
          </form>
          {staff.isLoading && <p>{t("admin.loading")}</p>}
          {staff.isError && <Alert tone="danger">{adminErrorMessage(staff.error, t("admin.loadError"))}</Alert>}
          <ul className="divide-y divide-line">
            {staff.data?.map((member) => (
              <li key={member.id} className="flex flex-wrap items-center justify-between gap-3 py-3">
                <span><strong>{member.name}</strong> · {member.email} · {i18n.resolvedLanguage === "en" ? member.role.label_en : member.role.label_fr}</span>
                <Button className="min-h-9 px-3 py-1 text-xs" variant="secondary" disabled={deleteStaff.isPending} onClick={() => {
                  if (window.confirm(t("admin.deleteStaffConfirm", { name: member.name }))) deleteStaff.mutate(member.id);
                }}>{t("admin.deleteStaff")}</Button>
              </li>
            ))}
          </ul>
        </Card>
      )}

      {isSuperAdmin && (
        <Card className="space-y-4">
          <div>
            <h2 className="font-title text-xl font-bold">{t("admin.taxonomy")}</h2>
            <p className="text-sm text-ink-2">{t("admin.taxonomyHint")}</p>
          </div>
          <form className="flex flex-wrap items-end gap-3" onSubmit={submitCategory}>
            <Field label={t("admin.categorySlug")} name="categorySlug" required value={categorySlug} onChange={(event) => setCategorySlug(event.target.value)} />
            <Button loading={createCategory.isPending}>{t("admin.createCategory")}</Button>
          </form>
          {categories.isError && <Alert tone="danger">{adminErrorMessage(categories.error, t("admin.loadError"))}</Alert>}
          <ul className="grid gap-2 sm:grid-cols-2">
            {categories.data?.map((category) => <li key={category.id} className="rounded-xl bg-ink/5 p-3">{category.slug} · {category.questions_count} {t("admin.questions").toLowerCase()}</li>)}
          </ul>
          {badges.isError && <Alert tone="danger">{adminErrorMessage(badges.error, t("admin.loadError"))}</Alert>}
          <div className="flex flex-wrap gap-2">
            {badges.data?.map((badge) => <span key={badge.id} className="rounded-full bg-ink/5 px-3 py-1 text-sm">{i18n.resolvedLanguage === "en" ? badge.name_en : badge.name_fr} · {badge.tier}</span>)}
          </div>
        </Card>
      )}

      {canManageContent && (
        <>
          <Card className="space-y-4">
            <div>
              <h2 className="font-title text-xl font-bold">{t("admin.imports")}</h2>
              <p className="text-sm text-ink-2">{t("admin.importsHint")}</p>
            </div>
            <form className="flex flex-wrap items-end gap-3" onSubmit={submitImport}>
              <label className="grid gap-1 text-sm font-bold">{t("admin.importFile")}
                <input className="max-w-full text-sm" type="file" accept=".csv,.txt,.json,.xlsx,.xls" required onChange={(event) => setImportFile(event.target.files?.[0] ?? null)} />
              </label>
              <Button loading={uploadImport.isPending}>{t("admin.uploadImport")}</Button>
            </form>
            {imports.isError && <Alert tone="danger">{adminErrorMessage(imports.error, t("admin.loadError"))}</Alert>}
            <ul className="divide-y divide-line">
              {imports.data?.map((job) => (
                <li key={job.id} className="space-y-2 py-3">
                  <div className="flex flex-wrap items-center justify-between gap-3">
                    <span><strong>{job.original_filename}</strong><br />{job.status} · {job.totals?.valides ?? 0}/{job.totals?.total ?? 0} {t("admin.validRows")} · {job.totals?.avertissements ?? 0} {t("admin.warnings")} · {job.totals?.erreurs ?? 0} {t("admin.errors")}</span>
                    <div className="flex flex-wrap gap-2">
                      <Button className="min-h-9 px-3 py-1 text-xs" variant="secondary" onClick={() => setSelectedImportId(selectedImportId === job.id ? null : job.id)}>{selectedImportId === job.id ? t("admin.hideImportReport") : t("admin.showImportReport")}</Button>
                      {job.status === "VALIDATED" && <Button className="min-h-9 px-3 py-1 text-xs" loading={confirmImport.isPending} onClick={() => confirmImport.mutate(job.id)}>{t("admin.confirmImport")}</Button>}
                      {!["CANCELLED", "IMPORTED"].includes(job.status) && <Button className="min-h-9 px-3 py-1 text-xs" variant="secondary" loading={cancelImport.isPending} onClick={() => cancelImport.mutate(job.id)}>{t("admin.cancelImport")}</Button>}
                    </div>
                  </div>
                  {selectedImportId === job.id && (
                    <div className="rounded-xl bg-ink/5 p-3 text-sm">
                      {importDetails.isLoading && <p>{t("admin.loading")}</p>}
                      {importDetails.isError && <Alert tone="danger">{adminErrorMessage(importDetails.error, t("admin.loadError"))}</Alert>}
                      {importDetails.data?.error_message && <Alert tone="danger">{importDetails.data.error_message}</Alert>}
                      <ul className="space-y-2">
                        {importDetails.data?.preview.map((row) => (
                          <li key={row.row_number} className="border-b border-line pb-2">
                            <strong>{t("admin.importRow", { row: row.row_number })} · {row.status}</strong>
                            {row.errors?.map((message, index) => <p key={`${row.row_number}-${index}`}>{message}</p>)}
                          </li>
                        ))}
                      </ul>
                    </div>
                  )}
                </li>
              ))}
            </ul>
          </Card>

          <Card className="space-y-4">
            <div>
              <h2 className="font-title text-xl font-bold">{t("admin.mediaLibrary")}</h2>
              <p className="text-sm text-ink-2">{t("admin.mediaHint")}</p>
            </div>
            <form className="grid gap-3 sm:grid-cols-2" onSubmit={submitMedia}>
              <label className="grid gap-1 text-sm font-bold sm:col-span-2">{t("admin.mediaFile")}
                <input type="file" accept="image/*" required onChange={(event) => setMediaFile(event.target.files?.[0] ?? null)} />
              </label>
              <Field label={t("admin.mediaLicense")} name="mediaLicense" required value={mediaLicense} onChange={(event) => setMediaLicense(event.target.value)} />
              <Field label={t("admin.mediaAuthor")} name="mediaAuthor" value={mediaAuthor} onChange={(event) => setMediaAuthor(event.target.value)} />
              <Field label={t("admin.mediaSource")} name="mediaSource" type="url" required value={mediaSource} onChange={(event) => setMediaSource(event.target.value)} />
              <Field label={t("admin.mediaAltFr")} name="mediaAltFr" required value={mediaAltFr} onChange={(event) => setMediaAltFr(event.target.value)} />
              <Field label={t("admin.mediaAltEn")} name="mediaAltEn" required value={mediaAltEn} onChange={(event) => setMediaAltEn(event.target.value)} />
              <Button className="sm:col-span-2" loading={uploadMedia.isPending}>{t("admin.uploadMedia")}</Button>
            </form>
            {media.isError && <Alert tone="danger">{adminErrorMessage(media.error, t("admin.loadError"))}</Alert>}
            <ul className="grid gap-2 sm:grid-cols-2">
              {media.data?.map((item) => <li key={item.id} className="rounded-xl bg-ink/5 p-3"><strong>{item.filename}</strong><br />{item.license ?? "—"} · {item.author ?? "—"}</li>)}
            </ul>
          </Card>
        </>
      )}

      {canManageUsers && (
        <Card className="space-y-3">
          <div>
            <h2 className="font-title text-xl font-bold">{t("admin.auditLog")}</h2>
            <p className="text-sm text-ink-2">{t("admin.auditHint")}</p>
          </div>
          {audit.isLoading && <p>{t("admin.loading")}</p>}
          {audit.isError && <Alert tone="danger">{adminErrorMessage(audit.error, t("admin.loadError"))}</Alert>}
          <ul className="divide-y divide-line">
            {audit.data?.map((entry) => <li key={entry.id} className="py-2 text-sm"><strong>{entry.action}</strong> · {entry.entity_type} · {entry.entity_id} <span className="text-ink-2">· {new Date(entry.created_at).toLocaleString()}</span></li>)}
          </ul>
          {audit.data?.length === 0 && <p className="text-sm text-ink-2">{t("admin.noAuditEntries")}</p>}
        </Card>
      )}
    </div>
  );
}
