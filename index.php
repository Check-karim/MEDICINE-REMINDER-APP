<?php

require __DIR__ . '/includes/init.php';

if (current_user()) {
    redirect(current_user()['role'] === 'admin' ? 'admin/index.php' : 'dashboard.php');
}

public_open('Home', 'home');
?>
<section class="hero">
  <div class="wrap hero-grid">
    <div>
      <p class="eyebrow">Digital Home Healthcare Ltd</p>
      <h1>Remember every dose, at the right time.</h1>
      <p class="lead">Record your medicines, set a dosage schedule, and get a reminder when it is time to take them. Look back at what you took, skipped, or missed, then download the record.</p>
      <div class="cta-row">
        <a class="btn btn-primary" href="<?= e(url('register.php')) ?>">Create an account</a>
        <a class="btn btn-secondary" href="<?= e(url('login.php')) ?>">Log in</a>
      </div>
    </div>
    <aside class="preview" aria-label="Example schedule">
      <div class="preview-top">
        <h2>Today</h2>
        <span class="pill pill-scheduled">Example</span>
      </div>
      <article class="dose">
        <div class="when"><strong>8:00</strong><span>AM</span></div>
        <div class="dose-body">
          <div class="dose-top"><h3>Amoxicillin</h3><span class="pill pill-taken">Taken</span></div>
          <p>500 mg · 1 capsule · Capsule</p>
        </div>
      </article>
      <article class="dose">
        <div class="when"><strong>1:00</strong><span>PM</span></div>
        <div class="dose-body">
          <div class="dose-top"><h3>Vitamin D</h3><span class="pill pill-due">Due now</span></div>
          <p>1000 IU · 1 tablet · Tablet</p>
        </div>
      </article>
      <article class="dose">
        <div class="when"><strong>8:00</strong><span>PM</span></div>
        <div class="dose-body">
          <div class="dose-top"><h3>Metformin</h3><span class="pill pill-scheduled">Scheduled</span></div>
          <p>500 mg · 1 tablet · Tablet</p>
        </div>
      </article>
    </aside>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <article class="install-card">
      <h2>Install on your phone or computer</h2>
      <p>Add Medicine Reminder to your home screen or desktop. It opens like an app, with the bottom menu.</p>
      <button class="btn btn-primary" type="button" data-install-app>Install app</button>
    </article>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <h2>A calmer way to manage medicines at home</h2>
    <p class="lede">The Medicine Reminder keeps the schedule in one place so a dose is easier to remember and easier to review.</p>
    <div class="feature-grid">
      <article class="feature">
        <div class="num">01</div>
        <h3>Record each medicine</h3>
        <p>Save the name, strength, form, and any note such as “after food”.</p>
      </article>
      <article class="feature">
        <div class="num">02</div>
        <h3>Set the dosage times</h3>
        <p>Choose the clock time, the amount, and the days of the week for every dose.</p>
      </article>
      <article class="feature">
        <div class="num">03</div>
        <h3>Track and export</h3>
        <p>Mark a dose as taken or skipped, then download the activity as a PDF or CSV file.</p>
      </article>
    </div>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <h2>How it works</h2>
    <div class="step-grid">
      <article class="step">
        <div class="num">1</div>
        <h3>Create your account</h3>
        <p>Use your name, a username, an email address, and a phone number.</p>
      </article>
      <article class="step">
        <div class="num">2</div>
        <h3>Add the schedule</h3>
        <p>Enter each medicine and the times you take it. Today’s list updates straight away.</p>
      </article>
      <article class="step">
        <div class="num">3</div>
        <h3>Follow the reminder</h3>
        <p>Open Remind when a dose is due. Your activity stays ready for a report.</p>
      </article>
    </div>
    <div class="cta-band">
      <h2>Built for care at home</h2>
      <p>Digital Home Healthcare Ltd uses this app to help patients organise medication and reduce missed doses.</p>
      <div class="cta-row">
        <a class="btn btn-primary" href="<?= e(url('register.php')) ?>">Get started</a>
        <a class="btn btn-secondary" href="<?= e(url('about.php')) ?>">About the company</a>
      </div>
    </div>
  </div>
</section>
<?php public_close(); ?>
