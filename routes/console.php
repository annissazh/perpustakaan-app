<?php

use App\Console\Commands\ProcessReservations;
use App\Console\Commands\SendDueReminders;
use Illuminate\Support\Facades\Schedule;

Schedule::command(SendDueReminders::class)
    ->dailyAt('07:00')
    ->withoutOverlapping()
    ->name('perpusapp:send-due-reminders');

Schedule::command(ProcessReservations::class)
    ->hourly()
    ->withoutOverlapping()
    ->name('perpusapp:process-reservations');
