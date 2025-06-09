// FORM HANDLER: Listen for form submission
document.getElementById('rates-form').addEventListener('submit', async (e) => {
  e.preventDefault(); // prevent page refresh

  // Gather form values
  const unitName = document.getElementById('unit-name').value;
  const arrival = document.getElementById('arrival').value;
  const departure = document.getElementById('departure').value;
  const occupants = parseInt(document.getElementById('occupants').value);
  const ages = document.getElementById('ages').value
    .split(',')
    .map(age => parseInt(age.trim())); // convert to number array

  // Create payload object
  const payload = {
    "Unit Name": unitName,
    "Arrival": arrival,
    "Departure": departure,
    "Occupants": occupants,
    "Ages": ages
  };

  try {
    // Send POST request to backend API
    const res = await fetch('http://localhost:8000/rates', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });

    // Read and parse JSON response
    const text = await res.text();
    const json = JSON.parse(text);

    // Build output string
    let output = `Unit Name: ${json['Unit Name']}\nDate Range: ${json['Date Range']}\n\nRates:\n`;
    json.Rates.forEach(rate => {
      output += `- Category: ${rate.Category}\n  Special Rate: ${rate['Special Rate']}\n  Daily Rate: ${rate['Effective Average Daily Rate']}\n  Total Charge: ${rate['Total Charge']}\n\n`;
    });

    // Display response
    document.getElementById('response-content').textContent = output;
    document.getElementById('response').classList.remove('hidden');

  } catch (error) {
    console.error("Error:", error);
    document.getElementById('response-content').textContent = "Error: " + error;
    document.getElementById('response').classList.remove('hidden');
  }
});

// SLIDER LOGIC
const slides = document.querySelectorAll('.slide'); // all slides
const dotsContainer = document.querySelector('.dots'); // dot container
let currentIndex = 0;
let interval;

// Dynamically create dot indicators
slides.forEach((_, idx) => {
  const dot = document.createElement('span');
  dot.addEventListener('click', () => showSlide(idx)); // jump to slide on click
  dotsContainer.appendChild(dot);
});

function showSlide(index) {
  slides.forEach(slide => slide.classList.remove('active'));
  slides[index].classList.add('active');

  // Activate correct dot
  const dots = document.querySelectorAll('.dots span');
  dots.forEach(dot => dot.classList.remove('active'));
  dots[index].classList.add('active');

  currentIndex = index;
}

function nextSlide() {
  currentIndex = (currentIndex + 1) % slides.length;
  showSlide(currentIndex);
}

function startSlider() {
  interval = setInterval(nextSlide, 5000); // switch every 5s
}

// Kick things off
showSlide(0);
startSlider();
