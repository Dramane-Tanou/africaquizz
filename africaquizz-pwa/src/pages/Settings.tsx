import { useState } from "react";
import { useNavigate } from "react-router-dom";
import { useTranslation } from "react-i18next";
import { useAuth } from "../context/AuthContext";
import { useSettings, type Theme } from "../context/SettingsContext";
import { authApi } from "../lib/endpoints";
import { normalizeError } from "../lib/api";
import { useErrorMessage } from "../hooks/useCommon";
import { useInstall } from "../lib/install";
import { Alert, Button, ButtonLink, Card, Field, PageTitle, Segmented, Toggle } from "../components/ui/primitives";

export default function Settings() {
  const { t } = useTranslation();
  const { settings, update } = useSettings();
  const { isAuthenticated, user, logout } = useAuth();
  const navigate = useNavigate();
  const errMsg = useErrorMessage();
  const install = useInstall();
  const [exporting, setExporting] = useState(false);
  const [msg, setMsg] = useState<{ tone: "danger" | "success"; text: string } | null>(null);
  const [showDelete, setShowDelete] = useState(false);
  const [password, setPassword] = useState("");
  const [deleting, setDeleting] = useState(false);

  async function exportData() {
    setExporting(true);
    setMsg(null);
    try {
      const data = await authApi.exportData();
      const blob = new Blob([JSON.stringify(data, null, 2)], { type: "application/json" });
      const a = document.createElement("a");
      a.href = URL.createObjectURL(blob);
      a.download = `africaquizz-mes-donnees-${new Date().toISOString().slice(0, 10)}.json`;
      a.click();
      URL.revokeObjectURL(a.href);
    } catch (e) {
      setMsg({ tone: "danger", text: errMsg(e) });
    } finally {
      setExporting(false);
    }
  }

  async function deleteAccount() {
    setDeleting(true);
    setMsg(null);
    try {
      await authApi.deleteAccount(password);
      await logout();
      navigate("/", { replace: true });
    } catch (e) {
      const n = normalizeError(e);
      setMsg({ tone: "danger", text: n.message || errMsg(e) });
    } finally {
      setDeleting(false);
    }
  }

  return (
    <div className="space-y-5">
      <PageTitle>⚙️ {t("settings.title")}</PageTitle>

      <Card className="space-y-5">
        <div>
          <p className="mb-2 font-bold">{t("settings.language")}</p>
          <Segmented
            label={t("settings.language")}
            value={settings.locale}
            onChange={(v) => update({ locale: v })}
            options={[
              { value: "fr", label: "Français" },
              { value: "en", label: "English" },
            ]}
          />
        </div>
        <div>
          <p className="mb-2 font-bold">{t("settings.theme")}</p>
          <Segmented<Theme>
            label={t("settings.theme")}
            value={settings.theme}
            onChange={(v) => update({ theme: v })}
            options={(["system", "light", "dark"] as Theme[]).map((v) => ({ value: v, label: t(`settings.theme_${v}`) }))}
          />
        </div>
        <Toggle checked={settings.hideTimer} onChange={(v) => update({ hideTimer: v })} label={t("settings.hideTimer")} hint={t("settings.hideTimerHint")} />
        <Toggle checked={settings.vibrate} onChange={(v) => update({ vibrate: v })} label={t("settings.sound")} />
        {install.canPrompt ? (
          <Button variant="green" block onClick={() => void install.prompt()}>
            📲 {t("settings.install")}
          </Button>
        ) : (
          !install.installed && (
            <ButtonLink to="/installer" variant="green" block>
              📲 {t("settings.install")}
            </ButtonLink>
          )
        )}
      </Card>

      {isAuthenticated && (
        <Card className="space-y-4">
          <h2 className="font-title text-xl font-semibold">{t("settings.account")}</h2>
          {user && <p className="text-sm text-ink-2">{user.email}</p>}
          <Button variant="secondary" block onClick={() => void logout().then(() => navigate("/", { replace: true }))}>
            {t("auth.logout")}
          </Button>

          <h3 className="pt-2 font-title text-lg font-semibold">{t("settings.data")}</h3>
          <Button variant="secondary" block loading={exporting} onClick={() => void exportData()}>
            ⬇️ {t("settings.export")}
          </Button>
          {!showDelete ? (
            <Button variant="ghost" block className="text-danger" onClick={() => setShowDelete(true)}>
              {t("settings.deleteAccount")}
            </Button>
          ) : (
            <div className="space-y-3 rounded-2xl border-2 border-danger/40 bg-danger-soft p-4">
              <p className="text-sm font-semibold text-danger">{t("settings.deleteIntro")}</p>
              <Field label={t("auth.password")} name="delete-password" type="password" autoComplete="current-password" value={password} onChange={(e) => setPassword(e.target.value)} />
              <div className="grid grid-cols-2 gap-3">
                <Button variant="secondary" onClick={() => setShowDelete(false)}>
                  {t("app.cancel")}
                </Button>
                <Button variant="danger" loading={deleting} disabled={!password} onClick={() => void deleteAccount()}>
                  {t("settings.deleteConfirm")}
                </Button>
              </div>
            </div>
          )}
        </Card>
      )}

      {msg && <Alert tone={msg.tone}>{msg.text}</Alert>}
      <p className="text-center text-xs text-ink-2">
        Africa Quizz · {t("settings.version", { v: __APP_VERSION__ })}
      </p>
    </div>
  );
}
