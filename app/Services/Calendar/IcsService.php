<?php

namespace App\Services\Calendar;

use Carbon\Carbon;

/**
 * ICS Calendar Service
 *
 * Generates .ics files for calendar invites.
 * Simple implementation - no external APIs (Google/Outlook).
 */
class IcsService
{
    /**
     * Generate ICS content for interview invitation.
     *
     * @param array $data Interview data
     * @return string ICS file content
     */
    public function generateInterviewIcs(array $data): string
    {
        $uid = $data['interview_id'] ?? uniqid('talentqx-');
        $companyName = $data['company_name'] ?? 'Company';
        $jobTitle = $data['job_title'] ?? 'Position';
        $interviewUrl = $data['interview_url'];
        $startTime = $data['start_time']; // Carbon instance
        $durationMinutes = $data['duration_minutes'] ?? 30;
        $timezone = $data['timezone'] ?? 'Europe/Istanbul';
        $locale = $data['locale'] ?? 'tr';

        // Calculate end time
        $endTime = $startTime->copy()->addMinutes($durationMinutes);

        // Format times for ICS (UTC)
        $dtStart = $startTime->copy()->utc()->format('Ymd\THis\Z');
        $dtEnd = $endTime->copy()->utc()->format('Ymd\THis\Z');
        $dtStamp = Carbon::now()->utc()->format('Ymd\THis\Z');

        // Title
        $summary = $locale === 'tr'
            ? "Mülakat – {$companyName}"
            : "Interview – {$companyName}";

        // Description
        $description = $locale === 'tr'
            ? "Online mülakat daveti\\n\\nŞirket: {$companyName}\\nPozisyon: {$jobTitle}\\n\\nMülakata katılmak için:\\n{$interviewUrl}\\n\\nSessiz bir ortamda, kamera ve mikrofon erişimi olan bir cihazdan katılmanızı öneririz."
            : "Online interview invitation\\n\\nCompany: {$companyName}\\nPosition: {$jobTitle}\\n\\nJoin interview:\\n{$interviewUrl}\\n\\nWe recommend joining from a quiet environment with camera and microphone access.";

        // Location (URL)
        $location = $interviewUrl;

        // Generate ICS content
        $ics = "BEGIN:VCALENDAR\r\n";
        $ics .= "VERSION:2.0\r\n";
        $ics .= "PRODID:-//TalentQX//Interview Platform//EN\r\n";
        $ics .= "CALSCALE:GREGORIAN\r\n";
        $ics .= "METHOD:REQUEST\r\n";
        $ics .= "BEGIN:VEVENT\r\n";
        $ics .= "UID:{$uid}@talentqx.com\r\n";
        $ics .= "DTSTAMP:{$dtStamp}\r\n";
        $ics .= "DTSTART:{$dtStart}\r\n";
        $ics .= "DTEND:{$dtEnd}\r\n";
        $ics .= "SUMMARY:{$this->escapeIcsText($summary)}\r\n";
        $ics .= "DESCRIPTION:{$this->escapeIcsText($description)}\r\n";
        $ics .= "LOCATION:{$this->escapeIcsText($location)}\r\n";
        $ics .= "URL:{$interviewUrl}\r\n";
        $ics .= "STATUS:CONFIRMED\r\n";
        $ics .= "SEQUENCE:0\r\n";
        $ics .= "BEGIN:VALARM\r\n";
        $ics .= "TRIGGER:-PT1H\r\n";
        $ics .= "ACTION:DISPLAY\r\n";
        $ics .= "DESCRIPTION:" . ($locale === 'tr' ? 'Mülakat 1 saat sonra' : 'Interview in 1 hour') . "\r\n";
        $ics .= "END:VALARM\r\n";
        $ics .= "BEGIN:VALARM\r\n";
        $ics .= "TRIGGER:-PT15M\r\n";
        $ics .= "ACTION:DISPLAY\r\n";
        $ics .= "DESCRIPTION:" . ($locale === 'tr' ? 'Mülakat 15 dakika sonra' : 'Interview in 15 minutes') . "\r\n";
        $ics .= "END:VALARM\r\n";
        $ics .= "END:VEVENT\r\n";
        $ics .= "END:VCALENDAR\r\n";

        return $ics;
    }

    /**
     * Get suggested filename for ICS file.
     */
    public function getFilename(string $companyName, string $locale = 'tr'): string
    {
        $slug = preg_replace('/[^a-zA-Z0-9]+/', '-', strtolower($companyName));
        $slug = trim($slug, '-');

        return $locale === 'tr'
            ? "mulakat-{$slug}.ics"
            : "interview-{$slug}.ics";
    }

    /**
     * Escape text for ICS format.
     */
    private function escapeIcsText(string $text): string
    {
        // Escape special characters per RFC 5545
        $text = str_replace('\\', '\\\\', $text);
        $text = str_replace(',', '\\,', $text);
        $text = str_replace(';', '\\;', $text);
        $text = str_replace("\n", '\\n', $text);
        $text = str_replace("\r", '', $text);

        return $text;
    }
}
