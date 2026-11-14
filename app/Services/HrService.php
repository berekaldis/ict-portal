<?php

namespace App\Services;

use App\Models\HrInternshipRequest;
use App\Models\HrQualification;
use App\Models\HrRecruitment;
use App\Models\HrSetting;
use App\Models\HrSystemConfig;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HrService
{
    /**
     * Generate unique sequential ID (e.g. IRF-00001, QF-00001, RF-00001).
     */
    public static function generateId(string $prefix, string $modelClass, string $idColumn = 'request_id'): string
    {
        $lastRecord = $modelClass::orderBy('id', 'desc')->first();
        $nextNum = 1;

        if ($lastRecord && preg_match('/' . preg_quote($prefix, '/') . '-(\d+)/', (string) $lastRecord->{$idColumn}, $matches)) {
            $nextNum = intval($matches[1]) + 1;
        }

        return $prefix . '-' . str_pad((string) $nextNum, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Dispatch HR Telegram alert to branch chat if configured.
     */
    public static function notifyBranch(string $branchName, string $title, array $details): bool
    {
        $botToken = HrSystemConfig::get('bot_token')
            ?: config('services.telegram.bot_token', '8485030522:AAFQrY-bzz15N1G_Xfen1zsrNPxNrPqf8k8');

        $chatId = HrSetting::where('type', 'branch')
            ->where('value', $branchName)
            ->value('chat_id');

        if (!$chatId) {
            // Fallback to default admin group if branch-specific chat ID is not set
            $chatId = config('services.telegram.chat_id_admin', '-1001502645326');
        }

        if (empty($botToken) || empty($chatId)) {
            return false;
        }

        $msg = "<b>{$title}</b>\n\n";
        foreach ($details as $label => $val) {
            $msg .= "<b>{$label}:</b> " . htmlspecialchars((string) $val, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "\n";
        }

        return TelegramService::sendMessage($chatId, $msg, $botToken);
    }

    /**
     * Return formatted Amharic letter details for an Internship Request.
     */
    public static function getInternshipLetterData(HrInternshipRequest $record): array
    {
        return [
            'type' => 'Internship Request',
            'title' => 'የሥራ ላይ ልምምድን ይመለከታል',
            'ref_no' => $record->request_id,
            'date' => $record->created_at?->format('Y-m-d') ?? date('Y-m-d'),
            'to_branch' => $record->to_branch,
            'from_dept' => 'ሰው ኃይል አስተዳደር',
            'name' => $record->name,
            'gender' => $record->gender,
            'work_branch' => $record->work_branch,
            'job' => $record->job,
            'start_date' => $record->start_date?->format('Y-m-d') ?? (string) $record->start_date,
            'body' => "{$record->gender} {$record->name} በ {$record->work_branch} ቅርንጫፍ ውስጥ በ {$record->job} የሥራ መደብ ላይ እንዲቀጠሩ ይታወቃል። ከ {$record->start_date} ጀምሮ በዚሁ የሥራ መደብ የሥራ ላይ ልምምድ እንዲያደርጉ ወደ እርስዎ ልከናል። ብቁ መሆናቸውን ሪፖርት ሲያደርጉልን፣ የቅጥር ውላቸውን እንደምንፈጽም እንገልጻለን።",
        ];
    }

    /**
     * Return formatted Amharic letter details for a Qualification Record.
     */
    public static function getQualificationLetterData(HrQualification $record): array
    {
        return [
            'type' => 'Qualification Confirmation',
            'title' => 'የሙከራ ጊዜ ማጠናቀቅን ይመለከታል',
            'ref_no' => $record->request_id,
            'date' => $record->created_at?->format('Y-m-d') ?? date('Y-m-d'),
            'to_branch' => $record->branch,
            'from_dept' => 'ሰው ኃይል አስተዳደር',
            'name' => $record->name,
            'gender' => $record->gender,
            'branch' => $record->branch,
            'job' => $record->job,
            'probation_start' => $record->probation_start?->format('Y-m-d') ?? (string) $record->probation_start,
            'manager_name' => $record->manager_name,
            'manager_rank' => $record->manager_rank,
            'destination' => $record->destination,
            'body' => "{$record->gender} {$record->name} በ {$record->branch} ቅርንጫፍ በ {$record->job} የሥራ መደብ ላይ ከ {$record->probation_start} ጀምሮ የገቡበትን የሙከራ ጊዜ በተሳካ ሁኔታ ያጠናቀቁ እና በቅርንጫፍ ሥራ አስኪያጅ {$record->manager_name} ({$record->manager_rank}) የተመሰከረላቸው መሆኑን እናረጋግጣለን።",
        ];
    }

    /**
     * Return formatted Amharic letter details for a Recruitment Notification.
     */
    public static function getRecruitmentLetterData(HrRecruitment $record): array
    {
        return [
            'type' => 'Recruitment Placement',
            'title' => 'የቋሚ ቅጥር ምደባን ይመለከታል',
            'ref_no' => $record->request_id,
            'date' => $record->created_at?->format('Y-m-d') ?? date('Y-m-d'),
            'to_branch' => $record->branch,
            'from_dept' => 'ሰው ኃይል አስተዳደር',
            'name' => $record->name,
            'gender' => $record->gender,
            'branch' => $record->branch,
            'requested_job' => $record->requested_job,
            'assigned_job' => $record->assigned_job,
            'start_date' => $record->start_date?->format('Y-m-d') ?? (string) $record->start_date,
            'destination' => $record->destination,
            'body' => "{$record->gender} {$record->name} ለ {$record->requested_job} የሥራ መደብ ጠይቀው የነበረ ቢሆንም፣ በድርጅታችን የሰው ኃይል ፍላጎት መሠረት በ {$record->branch} ቅርንጫፍ በ {$record->assigned_job} የሥራ መደብ ላይ ከ {$record->start_date} ጀምሮ በቋሚነት እንዲመደቡ ተወስኗል።",
        ];
    }
}
