<?php

require __DIR__ . '/includes/init.php';

$loggedIn = (bool) current_user();
if ($loggedIn) {
    app_open('About us', 'more');
} else {
    public_open('About us', 'about');
}
?>
<div class="about-block">
  <p class="eyebrow">Digital Home Healthcare Ltd</p>
  <h2>Care that fits around home life</h2>
  <p>Digital Home Healthcare Ltd supports patients who want their treatment to stay organised at home. The Medicine Reminder is the company’s tool for recording medicines, setting dosage schedules, and keeping a clear history of each dose.</p>
</div>
<div class="about-block">
  <h2>Why reminders matter</h2>
  <p>A missed dose is easy to overlook when several medicines are taken at different times. The app puts those times in one list, shows what is due, and keeps taken, skipped, and missed doses visible.</p>
</div>
<div class="about-block">
  <h2>What you can do</h2>
  <p>Patients can register, save their medicines, choose days and times, mark each dose, and download a PDF or CSV report. Staff can sign in with the predefined admin account, review patients and medicines, and download the same reports for one patient or for everyone.</p>
</div>
<div class="about-block">
  <h2>A note about medical advice</h2>
  <p>This app organises reminders and records. It does not decide a dose, change a prescription, or replace advice from a doctor, pharmacist, or nurse. Follow the instructions given with your medicine.</p>
</div>
<div class="about-block">
  <h2>Contact</h2>
  <p>Email the team at care@digitalhomehealthcare.test. Include your username if you are writing about an account.</p>
</div>
<?php
if ($loggedIn) {
    app_close('more');
} else {
    public_close();
}
