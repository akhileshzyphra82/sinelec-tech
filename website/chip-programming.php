<?php
$currentPage = 'chip-programming';
$pageTitle   = 'Chip Programming Services — Sinelec Tech';
require_once 'header.php';
?>

<main>
<div class="wrap page-wrap">

  <!-- Hero -->
  <div class="chip-hero">
    <div class="chip-hero-eyebrow">Expert Engineering Services</div>
    <h1 class="chip-hero-title">Semiconductor Programming &amp; Engineering Services</h1>
    <p class="chip-hero-sub">From single chip programming to full product development — we're your engineering partner. Fast 48-hour turnaround.</p>
    <div class="chip-hero-ctas">
      <a href="request-a-quote" class="btn btn-yellow btn-lg">Get a Quote</a>
      <a href="products?cat=mcu" class="btn btn-ghost-white btn-lg">Browse MCUs →</a>
    </div>
  </div>

  <!-- Services Grid (rendered by JS from STORE_DATA.services) -->
  <div class="home-section-wrap">
    <div class="sec-head">
      <div>
        <div class="sec-title">Our Services</div>
        <div class="sec-subtitle">Professional electronics engineering services for every project</div>
      </div>
    </div>
    <div class="srv-grid" id="srvGrid"></div>
  </div>

  <!-- How It Works -->
  <div class="home-section-wrap">
    <div class="sec-head">
      <div>
        <div class="sec-title">How Chip Programming Works</div>
        <div class="sec-subtitle">Simple, fast, and fully managed — from order to programmed chip</div>
      </div>
    </div>
    <div class="how-grid">
      <div class="how-step">
        <div class="how-step-num">1</div>
        <h4 class="how-step-title">Order Online</h4>
        <p class="how-step-desc">Select your MCU from our catalog and add the chip programming service at checkout</p>
      </div>
      <div class="how-step">
        <div class="how-step-num">2</div>
        <h4 class="how-step-title">Share Firmware</h4>
        <p class="how-step-desc">Email or WhatsApp us your firmware .hex / .bin file and programming specifications</p>
      </div>
      <div class="how-step">
        <div class="how-step-num">3</div>
        <h4 class="how-step-title">We Program</h4>
        <p class="how-step-desc">Our engineers flash your firmware and perform functional testing on every unit</p>
      </div>
      <div class="how-step">
        <div class="how-step-num">4</div>
        <h4 class="how-step-title">Fast Delivery</h4>
        <p class="how-step-desc">Programmed and tested chips dispatched within 24–48 hours of receiving your firmware</p>
      </div>
    </div>
    <div class="how-cta-row">
      <a href="request-a-quote" class="btn btn-orange btn-lg">Get a Quote for Your Project</a>
      <a href="products?cat=mcu" class="btn btn-outline btn-lg">Browse MCUs</a>
    </div>
  </div>

  <!-- Supported Platforms -->
  <div class="home-section-wrap">
    <div class="sec-head">
      <div>
        <div class="sec-title">Supported Platforms</div>
        <div class="sec-subtitle">We program all major MCU families</div>
      </div>
    </div>
    <div class="platform-grid">
      <?php
      $platforms = [
        ['name' => 'Arduino / AVR',  'note' => 'Uno, Nano, Mega, Pro Mini', 'logo' => 'https://cdn.jsdelivr.net/npm/simple-icons@v16/icons/arduino.svg', 'accent' => 'platform-card--teal'],
        ['name' => 'STM32',          'note' => 'F0 / F1 / F4 / G0 / H7 series', 'logo' => 'https://cdn.jsdelivr.net/npm/simple-icons@v16/icons/stmicroelectronics.svg', 'accent' => 'platform-card--blue'],
        ['name' => 'ESP32 / ESP8266','note' => 'Wi-Fi + BT, custom firmware', 'logo' => 'https://cdn.jsdelivr.net/npm/simple-icons@v16/icons/espressif.svg', 'accent' => 'platform-card--orange'],
        ['name' => 'PIC',            'note' => 'PIC16 / PIC18 / PIC32', 'logo' => 'https://cdn.jsdelivr.net/npm/simple-icons@v16/icons/microchiptechnology.svg', 'accent' => 'platform-card--red'],
        ['name' => 'nRF52 / nRF5340','note' => 'Bluetooth 5, Zigbee, Thread', 'logo' => 'https://cdn.jsdelivr.net/npm/simple-icons@v16/icons/nordicsemiconductor.svg', 'accent' => 'platform-card--indigo'],
        ['name' => 'RP2040',         'note' => 'Raspberry Pi Pico platform', 'logo' => 'https://cdn.jsdelivr.net/npm/simple-icons@v16/icons/raspberrypi.svg', 'accent' => 'platform-card--pink'],
        ['name' => 'MSP430',         'note' => 'Ultra-low-power TI MCUs', 'logo' => 'https://cdn.jsdelivr.net/npm/simple-icons@v16/icons/texasinstruments.svg', 'accent' => 'platform-card--purple'],
        ['name' => 'RISC-V',         'note' => 'GD32, CH32, custom cores', 'logo' => 'https://cdn.jsdelivr.net/npm/simple-icons@v16/icons/riscv.svg', 'accent' => 'platform-card--gold'],
      ];
      foreach ($platforms as $p):
      ?>
      <article class="platform-card <?= htmlspecialchars($p['accent']) ?>">
        <div class="platform-logo-wrap">
          <img
            src="<?= htmlspecialchars($p['logo']) ?>"
            alt="<?= htmlspecialchars($p['name']) ?> logo"
            class="platform-logo"
            loading="lazy"
            onerror="this.style.display='none'; this.parentElement.innerHTML='<span class=&quot;platform-logo-fallback&quot;><?= htmlspecialchars(substr($p['name'], 0, 2)) ?></span>';"
          >
        </div>
        <h3 class="platform-name"><?= htmlspecialchars($p['name']) ?></h3>
        <p class="platform-note"><?= htmlspecialchars($p['note']) ?></p>
      </article>
      <?php endforeach; ?>
    </div>
  </div>

</div>
</main>

<?php require_once 'footer.php'; ?>
