<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
  ->withRouting(
    web: __DIR__.'/../routes/web.php',
    api: __DIR__.'/../routes/api.php',
    commands: __DIR__.'/../routes/console.php',
    health: '/up',
  )
  ->withSchedule(function (\Illuminate\Console\Scheduling\Schedule $schedule) {
      $schedule->command('billing:generate')->dailyAt('01:00')->withoutOverlapping();
      $schedule->command('billing:remind')->dailyAt('08:00')->withoutOverlapping();
      $schedule->command('billing:disable-unpaid')->hourly()->withoutOverlapping();
      $schedule->command('billing:enable-paid')->everyFiveMinutes()->withoutOverlapping(); // Auto-ON jika sudah bayar
      $schedule->command('mikrotik:sync-all')->everyFiveMinutes()->withoutOverlapping();
      $schedule->command('monitor:pelanggan')->everyFiveMinutes()->withoutOverlapping();
      $schedule->command('bot:goodbye')->everyMinute()->withoutOverlapping();
      $schedule->command('status:publish')->everyMinute()->withoutOverlapping();
      $schedule->command('attendance:send-monthly-recap')->monthlyOn(10, '09:00')->withoutOverlapping();
  })
  ->withMiddleware(function (Middleware $middleware) {
      $middleware->validateCsrfTokens(except: [
          'payment/callback',
          'whatsapp/webhook',
          'whatsapp/status',
          'whatsapp/train',
          'iclock/*',
          '/iclock/*',
          'absensi/webhook',
          '/absensi/webhook',
      ]);

      $middleware->web(append: [
          \App\Http\Middleware\RestrictIntern::class,
      ]);
  })
  ->withExceptions(function (Exceptions $exceptions) {
    //
  })->create();