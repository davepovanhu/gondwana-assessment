// FORM HANDLER
document.getElementById('rates-form').addEventListener('submit', async (e) => {
  e.preventDefault();

  // Show loading animation
  document.getElementById('loader').classList.remove('hidden');
  document.getElementById('response').classList.add('hidden'); // hide old results

  const unitName = document.getElementById('unit-name').value;
  const arrival = document.getElementById('arrival').value;
  const departure = document.getElementById('departure').value;
  const occupants = parseInt(document.getElementById('occupants').value);
  const ages = document.getElementById('ages').value.split(',').map(age => parseInt(age.trim()));

  const payload = {
    "Unit Name": unitName,
    "Arrival": arrival,
    "Departure": departure,
    "Occupants": occupants,
    "Ages": ages
  };

  try {
    const res = await fetch('http://localhost:8000/rates', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    const text = await res.text();
    const json = JSON.parse(text);

    let output = `Unit Name: ${json['Unit Name']}\nDate Range: ${json['Date Range']}\n\nRates:\n`;
    json.Rates.forEach(rate => {
      output += `- Category: ${rate.Category}\n  Special Rate: ${rate['Special Rate']}\n  Daily Rate: ${rate['Effective Average Daily Rate']}\n  Total Charge: ${rate['Total Charge']}\n\n`;
    });

    document.getElementById('response-content').textContent = output;
  } catch (error) {
    console.error("Error:", error);
    document.getElementById('response-content').textContent = "Error: " + error;
  } finally {
    document.getElementById('loader').classList.add('hidden'); // Hide loader
    document.getElementById('response').classList.remove('hidden'); // Show response
  }
});

// SLIDER LOGIC
const slides = document.querySelectorAll('.slide');
const dotsContainer = document.querySelector('.dots');
let currentIndex = 0;
let interval;

// Create dots
slides.forEach((_, idx) => {
  const dot = document.createElement('span');
  dot.addEventListener('click', () => showSlide(idx));
  dotsContainer.appendChild(dot);
});

function showSlide(index) {
  slides.forEach(slide => slide.classList.remove('active'));
  slides[index].classList.add('active');

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
  interval = setInterval(nextSlide, 5000);
}

showSlide(0);
startSlider();
