import { useTranslation } from "react-i18next";
import { cx } from "../ui/primitives";
import type { AnswerRecord } from "../../lib/gameStore";
import type { QuestionOption } from "../../types/api";

/** Barre de temps calme (pas de compte à rebours alarmant), conforme au §7.4. */
export function TimerBar({ remainingMs, totalMs, hidden }: { remainingMs: number; totalMs: number; hidden?: boolean }) {
  const { t } = useTranslation();
  const pct = totalMs > 0 ? Math.max(0, Math.min(100, (remainingMs / totalMs) * 100)) : 0;
  if (hidden) return null;
  const low = pct < 25;
  return (
    <div
      className="h-2.5 w-full overflow-hidden rounded-full bg-ink/10"
      role="timer"
      aria-label={t("quiz.timeLeft")}
      aria-valuenow={Math.ceil(remainingMs / 1000)}
    >
      <div
        className={cx("h-full rounded-full transition-[width] duration-100 ease-linear", low ? "bg-brand-red" : "bg-brand-gold")}
        style={{ width: `${pct}%` }}
      />
    </div>
  );
}

type OptionState = "idle" | "selected" | "correct" | "wrong" | "dimmed";

const optionStyles: Record<OptionState, string> = {
  idle: "bg-surface hover:-translate-y-0.5 hover:bg-brand-gold/10",
  selected: "bg-brand-gold/30",
  correct: "bg-success-soft border-success text-success",
  wrong: "bg-danger-soft border-danger text-danger",
  dimmed: "bg-surface opacity-60",
};

export function OptionButton({
  option,
  state,
  disabled,
  onClick,
  index,
}: {
  option: QuestionOption;
  state: OptionState;
  disabled: boolean;
  onClick: () => void;
  index: number;
}) {
  return (
    <button
      type="button"
      onClick={onClick}
      disabled={disabled}
      aria-pressed={state === "selected" || undefined}
      aria-keyshortcuts={String(index + 1)}
      className={cx(
        "group flex min-h-[56px] w-full items-center gap-3 rounded-bubble border-2 border-black px-4 py-3 text-left font-semibold text-ink shadow-flat-sm transition-[transform,background-color] duration-150 dark:border-line",
        "disabled:cursor-default",
        optionStyles[state],
      )}
    >
      <span
        className={cx(
          "grid h-9 w-9 shrink-0 place-items-center rounded-full border-2 border-black font-title text-base font-bold dark:border-line",
          state === "correct" ? "bg-success text-white" : state === "wrong" ? "bg-danger text-white" : "bg-brand-gold text-black",
        )}
        aria-hidden
      >
        {state === "correct" ? "✓" : state === "wrong" ? "✕" : option.key}
      </span>
      <span className="flex-1">{option.label || option.key}</span>
    </button>
  );
}

export function optionState(key: string, record: AnswerRecord | undefined, pending: string | null): OptionState {
  if (!record) return pending === key ? "selected" : pending ? "dimmed" : "idle";
  if (record.correctAnswer === key) return "correct";
  if (record.given === key) return "wrong";
  return "dimmed";
}

export function FeedbackPanel({
  record,
  options,
  compact,
  children,
}: {
  record: AnswerRecord;
  options: QuestionOption[];
  compact?: boolean;
  children?: React.ReactNode;
}) {
  const { t } = useTranslation();
  const correctLabel = options.find((o) => o.key === record.correctAnswer)?.label;
  const title = record.correct ? t("quiz.correct") : record.given === null ? t("quiz.timeout") : t("quiz.wrong");
  return (
    <section
      aria-live="polite"
      className={cx(
        "animate-rise rounded-bubble border-2 p-4 motion-reduce:animate-none",
        record.correct ? "border-success/50 bg-success-soft" : "border-danger/50 bg-danger-soft",
      )}
    >
      <div className="flex items-center justify-between gap-3">
        <p className={cx("flex items-center gap-2 font-title text-xl font-bold", record.correct ? "text-success" : "text-danger")}>
          <span aria-hidden>{record.correct ? "✓" : "✕"}</span>
          {title}
        </p>
        {record.correct && (
          <p className="text-sm font-bold text-success">{t("quiz.pointsGained", { score: record.scoreGained, xp: record.xpGained })}</p>
        )}
      </div>
      {!record.correct && correctLabel && <p className="mt-1 text-sm font-semibold text-ink">{t("quiz.correctWas", { answer: correctLabel })}</p>}
      {record.correct && record.comboPct > 0 && (
        <p className="mt-1 text-sm font-bold text-warning">🔥 {t("quiz.combo", { n: record.comboStreak, pct: record.comboPct })}</p>
      )}
      {!compact && record.explanation && (
        <div className="mt-3 rounded-2xl bg-surface p-3 text-ink">
          <h3 className="text-xs font-bold uppercase tracking-wide text-ink-2">{t("quiz.explanation")}</h3>
          <p className="mt-1 text-[15px] leading-relaxed">{record.explanation}</p>
          {record.takeaway && (
            <>
              <h3 className="mt-3 text-xs font-bold uppercase tracking-wide text-ink-2">💡 {t("quiz.takeaway")}</h3>
              <p className="mt-1 text-[15px] font-semibold leading-relaxed">{record.takeaway}</p>
            </>
          )}
        </div>
      )}
      {children && <div className="mt-4">{children}</div>}
    </section>
  );
}
