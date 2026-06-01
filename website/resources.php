<?php
$currentPage = 'resources';
$pageTitle   = 'Resources — Sinelec Tech';
require_once 'header.php';
?>

<main>
<div class="wrap page-wrap">

  <!-- Page Header -->
  <div class="page-hero">
    <div class="page-eyebrow">Learning Centre</div>
    <h1 class="page-title">Resources &amp; Documentation</h1>
    <p class="page-sub">Datasheets, application notes, tutorials, and guides to help you build better electronics.</p>
  </div>

  <!-- Datasheets -->
  <section class="home-section-wrap">
    <div class="sec-head">
      <div>
        <div class="sec-title">Datasheets &amp; Specs</div>
        <div class="sec-subtitle">Official documentation for popular components</div>
      </div>
    </div>
    <div class="resources-grid">
      <div class="resource-card">
        <div class="resource-card-icon resource-card-icon--blue">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        </div>
        <div class="resource-card-title">STM32F103C8T6 Datasheet</div>
        <div class="resource-card-desc">Complete reference for the popular Blue Pill MCU — pinout, peripherals, electrical characteristics.</div>
        <a href="#" class="resource-card-link">Download PDF →</a>
      </div>
      <div class="resource-card">
        <div class="resource-card-icon resource-card-icon--blue">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        </div>
        <div class="resource-card-title">ESP32-WROOM-32 Technical Reference</div>
        <div class="resource-card-desc">Full technical reference manual for Espressif's ESP32 — Wi-Fi, BT, peripherals, memory map.</div>
        <a href="#" class="resource-card-link">Download PDF →</a>
      </div>
      <div class="resource-card">
        <div class="resource-card-icon resource-card-icon--blue">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        </div>
        <div class="resource-card-title">LM358 Op-Amp Datasheet</div>
        <div class="resource-card-desc">Texas Instruments LM358 dual op-amp — complete electrical specs, application circuits, and pinout.</div>
        <a href="#" class="resource-card-link">Download PDF →</a>
      </div>
      <div class="resource-card">
        <div class="resource-card-icon resource-card-icon--blue">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        </div>
        <div class="resource-card-title">ATmega328P Datasheet</div>
        <div class="resource-card-desc">Full datasheet for the Microchip ATmega328P used in Arduino Uno — ISP, PWM, ADC reference.</div>
        <a href="#" class="resource-card-link">Download PDF →</a>
      </div>
    </div>
  </section>

  <!-- Tutorials -->
  <section class="home-section-wrap">
    <div class="sec-head">
      <div>
        <div class="sec-title">Tutorials &amp; Application Notes</div>
        <div class="sec-subtitle">Step-by-step guides for common projects</div>
      </div>
    </div>
    <div class="resources-grid">
      <div class="resource-card">
        <div class="tutorial-card-video">
          <iframe
            src="https://www.youtube.com/embed/M7lc1UVf-VE"
            title="STM32 HAL tutorial example"
            loading="lazy"
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
            allowfullscreen
          ></iframe>
        </div>
        <div class="resource-card-title">Getting Started with STM32 HAL</div>
        <div class="resource-card-desc">Configure GPIO, timers, UART, and SPI on STM32 using STM32CubeIDE and HAL drivers.</div>
        <a href="#" class="resource-card-link">Read Guide →</a>
      </div>
      <div class="resource-card">
        <div class="tutorial-card-video">
          <iframe
            src="https://www.youtube.com/embed/jNQXAC9IVRw"
            title="ESP32 MQTT tutorial example"
            loading="lazy"
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
            allowfullscreen
          ></iframe>
        </div>
        <div class="resource-card-title">ESP32 Wi-Fi + MQTT IoT Project</div>
        <div class="resource-card-desc">Connect ESP32 to Wi-Fi, publish sensor data over MQTT to a cloud dashboard.</div>
        <a href="#" class="resource-card-link">Read Guide →</a>
      </div>
      <div class="resource-card">
        <div class="tutorial-card-video">
          <iframe
            src="https://www.youtube.com/embed/ysz5S6PUM-U"
            title="Op amp circuit design tutorial example"
            loading="lazy"
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
            allowfullscreen
          ></iframe>
        </div>
        <div class="resource-card-title">Op-Amp Circuit Design Basics</div>
        <div class="resource-card-desc">Inverting, non-inverting, comparator, and integrator circuits — practical design walkthrough.</div>
        <a href="#" class="resource-card-link">Read Guide →</a>
      </div>
      <div class="resource-card">
        <div class="tutorial-card-video">
          <iframe
            src="https://www.youtube.com/embed/dQw4w9WgXcQ"
            title="LM2596 buck converter tutorial example"
            loading="lazy"
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
            allowfullscreen
          ></iframe>
        </div>
        <div class="resource-card-title">Buck Converter Design with LM2596</div>
        <div class="resource-card-desc">Step-down regulator design — inductor selection, output filtering, PCB layout tips.</div>
        <a href="#" class="resource-card-link">Read Guide →</a>
      </div>
    </div>
  </section>

  <!-- FAQs -->
  <section class="home-section-wrap">
    <div class="sec-head">
      <div>
        <div class="sec-title">Frequently Asked Questions</div>
        <div class="sec-subtitle">Quick answers to common questions</div>
      </div>
    </div>
    <div class="resources-grid">
      <div class="resource-card">
        <div class="resource-card-icon resource-card-icon--orange">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        </div>
        <div class="resource-card-title">How do I verify component authenticity?</div>
        <div class="resource-card-desc">All our parts come with manufacturer lot codes and are sourced from authorised distributors. Certificate of Conformance available on request.</div>
        <a href="about#contact" class="resource-card-link">Contact Us →</a>
      </div>
      <div class="resource-card">
        <div class="resource-card-icon resource-card-icon--orange">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        </div>
        <div class="resource-card-title">What firmware formats do you support?</div>
        <div class="resource-card-desc">We accept .hex, .bin, .elf, and .srec files for chip programming. Provide your target device and programmer type when submitting.</div>
        <a href="chip-programming" class="resource-card-link">Chip Programming →</a>
      </div>
      <div class="resource-card">
        <div class="resource-card-icon resource-card-icon--orange">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        </div>
        <div class="resource-card-title">Do you offer bulk pricing?</div>
        <div class="resource-card-desc">Yes — volume discounts start at 10+ units for most products, and up to 40% off for orders of 100+. Submit a quote request for exact pricing.</div>
        <a href="request-a-quote" class="resource-card-link">Request Quote →</a>
      </div>
      <div class="resource-card">
        <div class="resource-card-icon resource-card-icon--orange">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        </div>
        <div class="resource-card-title">What is the shipping timeline?</div>
        <div class="resource-card-desc">In-stock orders placed before 2 PM are dispatched same day. Delivery is 1–4 business days depending on your location in India.</div>
        <a href="about#contact" class="resource-card-link">Contact Support →</a>
      </div>
    </div>
  </section>

</div>
</main>

<?php require_once 'footer.php'; ?>
