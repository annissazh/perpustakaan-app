<?php

namespace App\Console\Commands;

use App\Models\Borrowing;
use App\Models\SystemSetting;
use App\Notifications\BorrowingDueReminder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:send-due-reminders')]
#[Description('Mengirim pengingat jatuh tempo kepada anggota yang peminjamannya akan segera berakhir')]
class SendDueReminders extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $reminderDays = SystemSetting::getInt('hari_pengingat', 3);
        $today = now()->startOfDay();

        $borrowings = Borrowing::query()
            ->with(['user', 'book'])
            ->active()
            ->whereDate('due_date', '<=', $today->copy()->addDays($reminderDays)->toDateString())
            ->where(function ($query) use ($today): void {
                $query->whereNull('reminder_sent_at')
                    ->orWhereDate('reminder_sent_at', '<', $today->toDateString());
            })
            ->get();

        $sent = 0;

        foreach ($borrowings as $borrowing) {
            if (! $borrowing->user) {
                continue;
            }

            $borrowing->user->notify(new BorrowingDueReminder($borrowing));

            $borrowing->forceFill(['reminder_sent_at' => now()])->save();

            $sent++;
        }

        $this->info("Pengingat jatuh tempo terkirim: {$sent}.");

        return self::SUCCESS;
    }
}
