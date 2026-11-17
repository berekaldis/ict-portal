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
        $toBranch = $record->to_branch ?: $record->work_branch;
        $workBranch = $record->work_branch ?: $record->to_branch;
        $startDate = $record->start_date ? date('Y-m-d', strtotime((string) $record->start_date)) : date('Y-m-d');
        $letterDate = $record->created_at ? date('Y-m-d', strtotime((string) $record->created_at)) : date('Y-m-d');

        return [
            'type' => 'Internship Request',
            'title' => 'የሥራ ላይ ልምምድን ይመለከታል',
            'ref_no' => $record->request_id,
            'date' => $letterDate,
            'to_branch' => $toBranch,
            'from_dept' => 'ሰው ኃይል አስተዳደር',
            'name' => $record->name,
            'gender' => $record->gender,
            'work_branch' => $workBranch,
            'job' => $record->job,
            'start_date' => $startDate,
            'body' => "{$record->gender} {$record->name} በ {$workBranch} ቅርንጫፍ ውስጥ በ {$record->job} የሥራ መደብ ላይ እንዲቀጠሩ ይታወቃል። ከ {$startDate} ጀምሮ በዚሁ የሥራ መደብ የሥራ ላይ ልምምድ እንዲያደርጉ ወደ እርስዎ ልከናል። ብቁ መሆናቸውን ሪፖርት ሲያደርጉልን፣ የቅጥር ውላቸውን እንደምንፈጽም እንገልጻለን።",
        ];
    }

    /**
     * Return formatted Amharic letter details for a Qualification Record.
     */
    public static function getQualificationLetterData(HrQualification $record): array
    {
        $branch = $record->branch;
        $probationStart = $record->probation_start ? date('Y-m-d', strtotime((string) $record->probation_start)) : date('Y-m-d');
        $letterDate = $record->created_at ? date('Y-m-d', strtotime((string) $record->created_at)) : date('Y-m-d');

        return [
            'type' => 'Qualification Confirmation',
            'title' => 'የሙከራ ጊዜ ማጠናቀቅን ይመለከታል',
            'ref_no' => $record->request_id,
            'date' => $letterDate,
            'to_branch' => $branch,
            'from_dept' => 'ሰው ኃይል አስተዳደር',
            'name' => $record->name,
            'gender' => $record->gender,
            'branch' => $branch,
            'job' => $record->job,
            'probation_start' => $probationStart,
            'manager_name' => $record->manager_name,
            'manager_rank' => $record->manager_rank,
            'destination' => $record->destination,
            'body' => "{$record->gender} {$record->name} በ {$branch} ቅርንጫፍ በ {$record->job} የሥራ መደብ ላይ ከ {$probationStart} ጀምሮ የገቡበትን የሙከራ ጊዜ በተሳካ ሁኔታ ያጠናቀቁ እና በቅርንጫፍ ሥራ አስኪያጅ {$record->manager_name} ({$record->manager_rank}) የተመሰከረላቸው መሆኑን እናረጋግጣለን።",
        ];
    }

    /**
     * Return formatted Amharic letter details for a Recruitment Notification.
     */
    public static function getRecruitmentLetterData(HrRecruitment $record): array
    {
        $branch = $record->branch;
        $startDate = $record->start_date ? date('Y-m-d', strtotime((string) $record->start_date)) : date('Y-m-d');
        $letterDate = $record->created_at ? date('Y-m-d', strtotime((string) $record->created_at)) : date('Y-m-d');

        return [
            'type' => 'Recruitment Placement',
            'title' => 'የቋሚ ቅጥር ምደባን ይመለከታል',
            'ref_no' => $record->request_id,
            'date' => $letterDate,
            'to_branch' => $branch,
            'from_dept' => 'ሰው ኃይል አስተዳደር',
            'name' => $record->name,
            'gender' => $record->gender,
            'branch' => $branch,
            'requested_job' => $record->requested_job,
            'assigned_job' => $record->assigned_job,
            'start_date' => $startDate,
            'destination' => $record->destination,
            'body' => "{$record->gender} {$record->name} ለ {$record->requested_job} የሥራ መደብ ጠይቀው የነበረ ቢሆንም፣ በድርጅታችን የሰው ኃይል ፍላጎት መሠረት በ {$branch} ቅርንጫፍ በ {$record->assigned_job} የሥራ መደብ ላይ ከ {$startDate} ጀምሮ በቋሚነት እንዲመደቡ ተወስኗል።",
        ];
    }
}
