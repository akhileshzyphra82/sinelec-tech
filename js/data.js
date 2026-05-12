// ============================================================
// SINELEC TECH - Semiconductor E-Shop Data
// ============================================================

const STORE_DATA = {
  categories: [
    { id: "mcu", name: "Microcontrollers", icon: "cpu", count: 245, description: "ARM, AVR, PIC, STM32 series" },
    { id: "logic", name: "Logic ICs", icon: "grid", count: 189, description: "Gates, Flip-Flops, Counters" },
    { id: "opamp", name: "Op-Amps & Comparators", icon: "activity", count: 134, description: "Precision & general purpose" },
    { id: "power", name: "Power Management", icon: "zap", count: 210, description: "LDO, Buck, Boost converters" },
    { id: "transistor", name: "Transistors & MOSFETs", icon: "triangle", count: 320, description: "BJT, MOSFET, IGBT" },
    { id: "sensor", name: "Sensors & Modules", icon: "radio", count: 156, description: "Temp, Humidity, IMU, IR" },
    { id: "comm", name: "Communication ICs", icon: "wifi", count: 98, description: "UART, SPI, I2C, CAN, USB" },
    { id: "passive", name: "Passive Components", icon: "minus-circle", count: 540, description: "Resistors, Capacitors, Inductors" },
    { id: "memory", name: "Memory", icon: "database", count: 87, description: "Flash, EEPROM, SRAM, FRAM" },
    { id: "display", name: "Display & LED", icon: "monitor", count: 112, description: "OLED, LCD, LED drivers" }
  ],

  manufacturers: [
    "STMicroelectronics", "Texas Instruments", "Microchip Technology",
    "NXP Semiconductors", "Infineon", "ON Semiconductor", "Analog Devices",
    "Renesas", "Vishay", "ROHM", "Murata", "Wurth Elektronik",
    "Maxim Integrated", "Silicon Labs", "Nordic Semiconductor"
  ],

  products: [
    // ── Microcontrollers ──────────────────────────────────
    {
      id: 1, sku: "STM32F103C8T6", name: "STM32F103C8T6 ARM Cortex-M3 MCU",
      category: "mcu", manufacturer: "STMicroelectronics",
      image: "https://images.unsplash.com/photo-1518770660439-4636190af475?w=300&h=200&fit=crop",
      price: 285.00, originalPrice: 340.00,
      stock: 1250, minOrder: 1,
      rating: 4.8, reviews: 342,
      package: "LQFP-48", voltage: "2.0V - 3.6V", frequency: "72 MHz",
      description: "32-bit ARM Cortex-M3 microcontroller, 64KB Flash, 20KB RAM, 72MHz, multiple peripherals.",
      features: ["72 MHz Cortex-M3", "64KB Flash / 20KB RAM", "USB, CAN, USART, SPI, I2C", "LQFP-48 Package"],
      datasheet: "#", badge: "bestseller",
      priceBreaks: [{ qty: 1, price: 285 }, { qty: 10, price: 260 }, { qty: 100, price: 230 }],
      isNew: false, isFeatured: true
    },
    {
      id: 2, sku: "ATMEGA328P-PU", name: "ATmega328P-PU 8-bit AVR MCU",
      category: "mcu", manufacturer: "Microchip Technology",
      image: "https://images.unsplash.com/photo-1555664424-778a1e5e1b48?w=300&h=200&fit=crop",
      price: 195.00, originalPrice: 220.00,
      stock: 850, minOrder: 1,
      rating: 4.9, reviews: 890,
      package: "DIP-28", voltage: "1.8V - 5.5V", frequency: "20 MHz",
      description: "8-bit AVR microcontroller with 32KB ISP Flash and self-program capabilities. Arduino compatible.",
      features: ["20 MHz AVR Core", "32KB Flash / 2KB RAM", "USART, SPI, I2C, PWM", "Arduino Compatible"],
      datasheet: "#", badge: "popular",
      priceBreaks: [{ qty: 1, price: 195 }, { qty: 10, price: 175 }, { qty: 100, price: 150 }],
      isNew: false, isFeatured: true
    },
    {
      id: 3, sku: "PIC16F877A-I/P", name: "PIC16F877A 8-bit PIC Microcontroller",
      category: "mcu", manufacturer: "Microchip Technology",
      image: "https://images.unsplash.com/photo-1518770660439-4636190af475?w=300&h=200&fit=crop",
      price: 240.00, originalPrice: null,
      stock: 420, minOrder: 1,
      rating: 4.6, reviews: 215,
      package: "DIP-40", voltage: "2.0V - 5.5V", frequency: "20 MHz",
      description: "Enhanced Flash/EEPROM 8-bit Microcontroller with 10-bit A/D.",
      features: ["20 MHz", "14KB Flash / 368B RAM", "A/D, USART, SPI, I2C", "DIP-40"],
      datasheet: "#", badge: null,
      priceBreaks: [{ qty: 1, price: 240 }, { qty: 10, price: 215 }],
      isNew: false, isFeatured: false
    },
    {
      id: 4, sku: "ESP32-WROOM-32E", name: "ESP32-WROOM-32E WiFi+BT Module",
      category: "mcu", manufacturer: "Espressif Systems",
      image: "https://images.unsplash.com/photo-1563770660941-20978e870e26?w=300&h=200&fit=crop",
      price: 320.00, originalPrice: 380.00,
      stock: 2100, minOrder: 1,
      rating: 4.9, reviews: 1240,
      package: "SMD Module", voltage: "3.0V - 3.6V", frequency: "240 MHz",
      description: "Dual-core Xtensa LX6 MCU with WiFi 802.11b/g/n and Bluetooth 4.2/BLE.",
      features: ["240 MHz Dual-core", "4MB Flash / 520KB SRAM", "WiFi + Bluetooth 4.2/BLE", "38 GPIO pins"],
      datasheet: "#", badge: "hot",
      priceBreaks: [{ qty: 1, price: 320 }, { qty: 10, price: 290 }, { qty: 100, price: 255 }],
      isNew: false, isFeatured: true
    },
    {
      id: 5, sku: "STM32G0B1RET6", name: "STM32G0B1RET6 Cortex-M0+ MCU",
      category: "mcu", manufacturer: "STMicroelectronics",
      image: "https://images.unsplash.com/photo-1518770660439-4636190af475?w=300&h=200&fit=crop",
      price: 410.00, originalPrice: null,
      stock: 380, minOrder: 1,
      rating: 4.7, reviews: 128,
      package: "LQFP-64", voltage: "1.7V - 3.6V", frequency: "64 MHz",
      description: "Mainstream 64 MHz Arm Cortex-M0+ MCU with 512KB Flash, 144KB RAM.",
      features: ["64 MHz Cortex-M0+", "512KB Flash / 144KB RAM", "USB, FDCAN, USART, SPI, I2C", "LQFP-64"],
      datasheet: "#", badge: "new",
      priceBreaks: [{ qty: 1, price: 410 }, { qty: 10, price: 375 }],
      isNew: true, isFeatured: false
    },

    // ── Logic ICs ────────────────────────────────────────
    {
      id: 6, sku: "74HC595N", name: "74HC595 8-bit Shift Register",
      category: "logic", manufacturer: "Texas Instruments",
      image: "https://images.unsplash.com/photo-1518770660439-4636190af475?w=300&h=200&fit=crop",
      price: 22.00, originalPrice: null,
      stock: 5000, minOrder: 5,
      rating: 4.8, reviews: 567,
      package: "DIP-16", voltage: "2V - 6V", frequency: "—",
      description: "8-Bit Shift Registers With 3-State Output Registers.",
      features: ["8-bit serial/parallel", "3-State outputs", "Serial/parallel input", "DIP-16 / SOIC-16"],
      datasheet: "#", badge: "popular",
      priceBreaks: [{ qty: 5, price: 22 }, { qty: 50, price: 18 }, { qty: 500, price: 14 }],
      isNew: false, isFeatured: true
    },
    {
      id: 7, sku: "SN74HC00N", name: "SN74HC00 Quad 2-Input NAND Gate",
      category: "logic", manufacturer: "Texas Instruments",
      image: "https://images.unsplash.com/photo-1518770660439-4636190af475?w=300&h=200&fit=crop",
      price: 15.00, originalPrice: null,
      stock: 8000, minOrder: 10,
      rating: 4.7, reviews: 234,
      package: "DIP-14", voltage: "2V - 6V", frequency: "—",
      description: "Quad 2-Input NAND Gate with high-speed CMOS logic.",
      features: ["4 × 2-input NAND", "CMOS logic", "5ns propagation delay", "DIP-14"],
      datasheet: "#", badge: null,
      priceBreaks: [{ qty: 10, price: 15 }, { qty: 100, price: 12 }, { qty: 1000, price: 9 }],
      isNew: false, isFeatured: false
    },

    // ── Op-Amps ──────────────────────────────────────────
    {
      id: 8, sku: "LM741CN", name: "LM741 General Purpose Op-Amp",
      category: "opamp", manufacturer: "Texas Instruments",
      image: "https://images.unsplash.com/photo-1518770660439-4636190af475?w=300&h=200&fit=crop",
      price: 28.00, originalPrice: null,
      stock: 3200, minOrder: 1,
      rating: 4.5, reviews: 412,
      package: "DIP-8", voltage: "±22V max", frequency: "1 MHz GBW",
      description: "General purpose operational amplifier with overload protection.",
      features: ["±22V supply", "1 MHz GBW", "25mA output current", "DIP-8 / TO-5"],
      datasheet: "#", badge: null,
      priceBreaks: [{ qty: 1, price: 28 }, { qty: 10, price: 24 }, { qty: 100, price: 20 }],
      isNew: false, isFeatured: false
    },
    {
      id: 9, sku: "LM358P", name: "LM358 Dual Op-Amp",
      category: "opamp", manufacturer: "Texas Instruments",
      image: "https://images.unsplash.com/photo-1518770660439-4636190af475?w=300&h=200&fit=crop",
      price: 18.00, originalPrice: null,
      stock: 6500, minOrder: 1,
      rating: 4.7, reviews: 678,
      package: "DIP-8", voltage: "3V - 32V", frequency: "1 MHz GBW",
      description: "Dual operational amplifier, single supply, wide supply range.",
      features: ["Single supply: 3V–32V", "Dual supply: ±1.5V–±16V", "1 MHz GBW", "Large output swing"],
      datasheet: "#", badge: "bestseller",
      priceBreaks: [{ qty: 1, price: 18 }, { qty: 10, price: 15 }, { qty: 100, price: 12 }],
      isNew: false, isFeatured: true
    },

    // ── Power Management ─────────────────────────────────
    {
      id: 10, sku: "LM7805CT", name: "LM7805 +5V Linear Voltage Regulator",
      category: "power", manufacturer: "Texas Instruments",
      image: "https://images.unsplash.com/photo-1518770660439-4636190af475?w=300&h=200&fit=crop",
      price: 25.00, originalPrice: null,
      stock: 4800, minOrder: 1,
      rating: 4.9, reviews: 1105,
      package: "TO-220", voltage: "7V - 35V input", frequency: "—",
      description: "+5V fixed linear voltage regulator, 1A output current.",
      features: ["+5V fixed output", "1.5A max output", "Thermal shutdown", "TO-220 package"],
      datasheet: "#", badge: "bestseller",
      priceBreaks: [{ qty: 1, price: 25 }, { qty: 10, price: 22 }, { qty: 100, price: 18 }],
      isNew: false, isFeatured: true
    },
    {
      id: 11, sku: "AMS1117-3.3", name: "AMS1117 3.3V LDO Regulator",
      category: "power", manufacturer: "Advanced Monolithic Systems",
      image: "https://images.unsplash.com/photo-1518770660439-4636190af475?w=300&h=200&fit=crop",
      price: 12.00, originalPrice: null,
      stock: 9200, minOrder: 5,
      rating: 4.8, reviews: 890,
      package: "SOT-223", voltage: "4.75V - 15V input", frequency: "—",
      description: "3.3V LDO Linear Regulator, 800mA output current.",
      features: ["3.3V output", "800mA output", "Dropout: 1.1V", "SOT-223 / TO-252"],
      datasheet: "#", badge: "popular",
      priceBreaks: [{ qty: 5, price: 12 }, { qty: 50, price: 10 }, { qty: 500, price: 8 }],
      isNew: false, isFeatured: false
    },
    {
      id: 12, sku: "MP2307DN", name: "MP2307 3A 23V DC-DC Buck Converter",
      category: "power", manufacturer: "Monolithic Power Systems",
      image: "https://images.unsplash.com/photo-1518770660439-4636190af475?w=300&h=200&fit=crop",
      price: 85.00, originalPrice: 105.00,
      stock: 720, minOrder: 1,
      rating: 4.7, reviews: 234,
      package: "SOIC-8", voltage: "4.75V - 23V input", frequency: "340 kHz",
      description: "3A, 23V, 340kHz synchronous rectified step-down converter.",
      features: ["3A output current", "4.75V–23V input", "340 kHz switching", "100% duty cycle"],
      datasheet: "#", badge: "new",
      priceBreaks: [{ qty: 1, price: 85 }, { qty: 10, price: 75 }, { qty: 100, price: 65 }],
      isNew: true, isFeatured: false
    },

    // ── Transistors & MOSFETs ────────────────────────────
    {
      id: 13, sku: "BC547B", name: "BC547B NPN General Purpose Transistor",
      category: "transistor", manufacturer: "Vishay",
      image: "https://images.unsplash.com/photo-1518770660439-4636190af475?w=300&h=200&fit=crop",
      price: 4.00, originalPrice: null,
      stock: 25000, minOrder: 10,
      rating: 4.8, reviews: 1567,
      package: "TO-92", voltage: "45V Vce", frequency: "300 MHz fT",
      description: "NPN general purpose transistor, low noise, 45V, 100mA.",
      features: ["NPN silicon", "45V Vce, 50V Vcbo", "100mA collector current", "300 MHz fT"],
      datasheet: "#", badge: "bestseller",
      priceBreaks: [{ qty: 10, price: 4 }, { qty: 100, price: 3.2 }, { qty: 1000, price: 2.5 }],
      isNew: false, isFeatured: true
    },
    {
      id: 14, sku: "IRF540N", name: "IRF540N N-Channel Power MOSFET",
      category: "transistor", manufacturer: "Infineon",
      image: "https://images.unsplash.com/photo-1518770660439-4636190af475?w=300&h=200&fit=crop",
      price: 45.00, originalPrice: 55.00,
      stock: 1800, minOrder: 1,
      rating: 4.7, reviews: 423,
      package: "TO-220", voltage: "100V Vds", frequency: "—",
      description: "N-Channel power MOSFET, 100V, 33A, 44mΩ RDS(on).",
      features: ["100V / 33A", "44mΩ RDS(on)", "TO-220 package", "Fast switching"],
      datasheet: "#", badge: null,
      priceBreaks: [{ qty: 1, price: 45 }, { qty: 10, price: 40 }, { qty: 100, price: 35 }],
      isNew: false, isFeatured: false
    },

    // ── Sensors ──────────────────────────────────────────
    {
      id: 15, sku: "DHT22", name: "DHT22 Temperature & Humidity Sensor",
      category: "sensor", manufacturer: "Aosong",
      image: "https://images.unsplash.com/photo-1563770660941-20978e870e26?w=300&h=200&fit=crop",
      price: 120.00, originalPrice: 150.00,
      stock: 980, minOrder: 1,
      rating: 4.6, reviews: 756,
      package: "4-pin SIP", voltage: "3.3V - 5.5V", frequency: "—",
      description: "Digital temperature and humidity sensor with single-wire interface.",
      features: ["±0.5°C temp accuracy", "±2% RH accuracy", "-40°C to +80°C range", "Single wire interface"],
      datasheet: "#", badge: "popular",
      priceBreaks: [{ qty: 1, price: 120 }, { qty: 10, price: 105 }, { qty: 100, price: 90 }],
      isNew: false, isFeatured: true
    },
    {
      id: 16, sku: "MPU6050", name: "MPU-6050 6-Axis IMU Sensor",
      category: "sensor", manufacturer: "InvenSense",
      image: "https://images.unsplash.com/photo-1563770660941-20978e870e26?w=300&h=200&fit=crop",
      price: 95.00, originalPrice: null,
      stock: 1420, minOrder: 1,
      rating: 4.8, reviews: 934,
      package: "QFN-24", voltage: "2.375V - 3.46V", frequency: "—",
      description: "3-axis gyroscope + 3-axis accelerometer with I2C interface.",
      features: ["3-axis gyro + 3-axis accel", "I2C interface", "Digital motion processor", "QFN-24"],
      datasheet: "#", badge: "popular",
      priceBreaks: [{ qty: 1, price: 95 }, { qty: 10, price: 85 }, { qty: 100, price: 72 }],
      isNew: false, isFeatured: true
    },
    {
      id: 17, sku: "HC-SR04", name: "HC-SR04 Ultrasonic Distance Sensor",
      category: "sensor", manufacturer: "Generic",
      image: "https://images.unsplash.com/photo-1563770660941-20978e870e26?w=300&h=200&fit=crop",
      price: 55.00, originalPrice: null,
      stock: 3200, minOrder: 1,
      rating: 4.5, reviews: 1102,
      package: "Module", voltage: "5V DC", frequency: "40 kHz",
      description: "Ultrasonic ranging module, 2cm–400cm, non-contact distance measurement.",
      features: ["2cm – 400cm range", "±3mm accuracy", "40kHz ultrasonic", "Trigger/Echo interface"],
      datasheet: "#", badge: null,
      priceBreaks: [{ qty: 1, price: 55 }, { qty: 10, price: 48 }, { qty: 100, price: 40 }],
      isNew: false, isFeatured: false
    },

    // ── Communication ICs ────────────────────────────────
    {
      id: 18, sku: "MAX232CPE", name: "MAX232 RS-232 Line Driver/Receiver",
      category: "comm", manufacturer: "Maxim Integrated",
      image: "https://images.unsplash.com/photo-1518770660439-4636190af475?w=300&h=200&fit=crop",
      price: 38.00, originalPrice: null,
      stock: 2100, minOrder: 1,
      rating: 4.7, reviews: 312,
      package: "DIP-16", voltage: "5V", frequency: "—",
      description: "Dual EIA-232 drivers/receivers with ±15kV ESD protection.",
      features: ["Dual EIA-232 driver/receiver", "+5V only supply", "120kbps data rate", "±15kV ESD"],
      datasheet: "#", badge: null,
      priceBreaks: [{ qty: 1, price: 38 }, { qty: 10, price: 34 }],
      isNew: false, isFeatured: false
    },
    {
      id: 19, sku: "nRF24L01+", name: "nRF24L01+ 2.4GHz RF Transceiver",
      category: "comm", manufacturer: "Nordic Semiconductor",
      image: "https://images.unsplash.com/photo-1563770660941-20978e870e26?w=300&h=200&fit=crop",
      price: 75.00, originalPrice: 90.00,
      stock: 1560, minOrder: 1,
      rating: 4.7, reviews: 587,
      package: "Module", voltage: "1.9V - 3.6V", frequency: "2.4 GHz",
      description: "2.4GHz ISM band transceiver, 2Mbps data rate, SPI interface.",
      features: ["2.4GHz ISM band", "2Mbps air data rate", "SPI interface", "250kbps – 2Mbps"],
      datasheet: "#", badge: "popular",
      priceBreaks: [{ qty: 1, price: 75 }, { qty: 10, price: 65 }, { qty: 100, price: 55 }],
      isNew: false, isFeatured: false
    },

    // ── Memory ───────────────────────────────────────────
    {
      id: 20, sku: "AT24C256C-SSHL-T", name: "AT24C256 256Kb I2C EEPROM",
      category: "memory", manufacturer: "Microchip Technology",
      image: "https://images.unsplash.com/photo-1518770660439-4636190af475?w=300&h=200&fit=crop",
      price: 32.00, originalPrice: null,
      stock: 3400, minOrder: 1,
      rating: 4.6, reviews: 198,
      package: "SOIC-8", voltage: "1.7V - 5.5V", frequency: "400 kHz I2C",
      description: "256Kbit (32K × 8) 2-wire serial EEPROM with hardware write protect.",
      features: ["256Kbit EEPROM", "I2C, 400kHz", "1M write cycles", "100-year data retention"],
      datasheet: "#", badge: null,
      priceBreaks: [{ qty: 1, price: 32 }, { qty: 10, price: 28 }, { qty: 100, price: 24 }],
      isNew: false, isFeatured: false
    },

    // ── Passive ──────────────────────────────────────────
    {
      id: 21, sku: "RES-10K-1/4W-CF", name: "10kΩ 1/4W Carbon Film Resistor (Pack of 100)",
      category: "passive", manufacturer: "Yageo",
      image: "https://images.unsplash.com/photo-1518770660439-4636190af475?w=300&h=200&fit=crop",
      price: 35.00, originalPrice: null,
      stock: 50000, minOrder: 1,
      rating: 4.9, reviews: 2341,
      package: "Through-hole", voltage: "250V max", frequency: "—",
      description: "Pack of 100 × 10kΩ ±5% 1/4W carbon film resistors.",
      features: ["10kΩ ±5% tolerance", "1/4W power rating", "Pack of 100", "Through-hole axial"],
      datasheet: "#", badge: "bestseller",
      priceBreaks: [{ qty: 1, price: 35 }, { qty: 10, price: 30 }, { qty: 50, price: 25 }],
      isNew: false, isFeatured: false
    },
    {
      id: 22, sku: "CAP-100UF-25V-EL", name: "100µF 25V Electrolytic Capacitor (Pack of 50)",
      category: "passive", manufacturer: "Nichicon",
      image: "https://images.unsplash.com/photo-1518770660439-4636190af475?w=300&h=200&fit=crop",
      price: 65.00, originalPrice: null,
      stock: 18000, minOrder: 1,
      rating: 4.8, reviews: 876,
      package: "Through-hole", voltage: "25V", frequency: "—",
      description: "Pack of 50 × 100µF 25V electrolytic capacitors, general purpose.",
      features: ["100µF ±20% capacitance", "25V rated voltage", "Pack of 50", "8mm × 12mm"],
      datasheet: "#", badge: null,
      priceBreaks: [{ qty: 1, price: 65 }, { qty: 10, price: 58 }],
      isNew: false, isFeatured: false
    },

    // ── Display ──────────────────────────────────────────
    {
      id: 23, sku: "SSD1306-OLED-128X64", name: "SSD1306 0.96\" OLED Display Module",
      category: "display", manufacturer: "Solomon Systech",
      image: "https://images.unsplash.com/photo-1563770660941-20978e870e26?w=300&h=200&fit=crop",
      price: 185.00, originalPrice: 220.00,
      stock: 870, minOrder: 1,
      rating: 4.8, reviews: 1034,
      package: "Module", voltage: "3.3V / 5V", frequency: "I2C 400kHz",
      description: "0.96-inch 128×64 OLED display module with SSD1306 driver, I2C interface.",
      features: ["128×64 pixels", "I2C interface", "3.3V/5V compatible", "Self-luminous OLED"],
      datasheet: "#", badge: "popular",
      priceBreaks: [{ qty: 1, price: 185 }, { qty: 10, price: 165 }, { qty: 100, price: 145 }],
      isNew: false, isFeatured: true
    },
    {
      id: 24, sku: "HD44780-LCD-1602", name: "HD44780 16×2 Character LCD Module",
      category: "display", manufacturer: "Hitachi",
      image: "https://images.unsplash.com/photo-1563770660941-20978e870e26?w=300&h=200&fit=crop",
      price: 110.00, originalPrice: null,
      stock: 1240, minOrder: 1,
      rating: 4.6, reviews: 654,
      package: "Module", voltage: "5V", frequency: "—",
      description: "16-character × 2-line alphanumeric LCD module with HD44780 controller.",
      features: ["16×2 characters", "HD44780 compatible", "4/8-bit parallel interface", "Backlight included"],
      datasheet: "#", badge: null,
      priceBreaks: [{ qty: 1, price: 110 }, { qty: 10, price: 98 }, { qty: 100, price: 85 }],
      isNew: false, isFeatured: false
    }
  ],

  services: [
    {
      id: "s1", icon: "code",
      title: "Chip Programming",
      description: "We program and flash firmware onto your microcontrollers — Arduino, STM32, PIC, AVR, ESP32 and more. Custom firmware as per your requirements.",
      features: ["All major MCU families", "Custom firmware development", "JTAG / ISP / UART flashing", "Testing & verification"],
      price: "Starting ₹499/chip"
    },
    {
      id: "s2", icon: "cpu",
      title: "PCB Design & Assembly",
      description: "Professional PCB schematic design and component assembly services. From prototype to production-ready boards.",
      features: ["Schematic capture", "PCB layout (2–8 layers)", "Component sourcing", "Quality inspection"],
      price: "Starting ₹2,499/board"
    },
    {
      id: "s3", icon: "zap",
      title: "Custom Embedded Solutions",
      description: "End-to-end embedded system development tailored to your product requirements. IoT, industrial, consumer electronics.",
      features: ["Requirements analysis", "Hardware + firmware design", "Prototype → production", "Ongoing support"],
      price: "Get a quote"
    },
    {
      id: "s4", icon: "shield",
      title: "Component Testing & Sourcing",
      description: "We source genuine components from authorised distributors and verify authenticity before dispatch.",
      features: ["100% genuine parts", "Counterfeit detection", "Bulk procurement", "Express delivery"],
      price: "Free for orders > ₹5,000"
    }
  ],

  testimonials: [
    { name: "Rajesh Kumar", company: "RK Electronics, Delhi", rating: 5, text: "Ordered STM32 chips and got them programmed with our custom firmware. Fast service, genuine parts. Will order again!" },
    { name: "Priya Sharma", company: "TechMakers Lab, Bangalore", rating: 5, text: "Excellent product quality and very fast shipping. The chip programming service saved us a lot of time." },
    { name: "Mohammed Ali", company: "Robo Innovations, Hyderabad", rating: 4, text: "Great range of components. Prices are competitive and customer support is helpful. Highly recommended." },
    { name: "Anita Patel", company: "EduBot, Ahmedabad", rating: 5, text: "We buy all our Arduino and ESP32 modules from Sinelec. Best prices and fast delivery in India." }
  ],

  banners: [
    {
      title: "India's Premier Semiconductor Store",
      subtitle: "Genuine Components · Expert Programming · Fast Shipping",
      cta: "Shop Now",
      badge: "250,000+ Products",
      bg: "primary"
    },
    {
      title: "Chip Programming Service",
      subtitle: "Custom firmware flashed to your MCU — Arduino, STM32, PIC, ESP32",
      cta: "Learn More",
      badge: "Starting ₹499",
      bg: "secondary"
    },
    {
      title: "Bulk Orders Welcome",
      subtitle: "Competitive pricing on volume orders. Get a custom quote today.",
      cta: "Get Quote",
      badge: "Upto 40% Off",
      bg: "accent"
    }
  ]
};
