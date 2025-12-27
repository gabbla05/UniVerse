const search = document.querySelector('input[placeholder="Search"]');
const eventContainer = document.querySelector('.events-grid');
const filterButtons = document.querySelectorAll('.filter-btn:not(#archive-btn)'); // Zwykłe filtry
const archiveBtn = document.getElementById('archive-btn'); // Przycisk archiwum (może nie istnieć u studenta)

let currentCategory = 'All'; 
let isArchiveMode = false; // Nowa flaga stanu

function fetchEvents(query = "") {
    const data = {
        search: query,
        isArchive: isArchiveMode // Wysyłamy stan do backendu
    };

    fetch("/search", {
        method: "POST",
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(events => {
        renderEvents(events);
    })
    .catch(error => console.error('Error fetching events:', error));
}

// Start
document.addEventListener("DOMContentLoaded", () => {
    fetchEvents(""); 
});

// Szukanie
search.addEventListener("keyup", function (event) {
    if (event.key === "Enter") event.preventDefault();
    fetchEvents(this.value);
});

// Kliknięcie w zwykłe filtry (All, Sport...)
filterButtons.forEach(button => {
    button.addEventListener('click', function() {
        // Wyłącz tryb archiwum, jeśli był włączony
        if (isArchiveMode) {
            isArchiveMode = false;
            if(archiveBtn) archiveBtn.classList.remove('active');
            // Zmień styl przycisku archiwum na normalny
            if(archiveBtn) archiveBtn.style.backgroundColor = "transparent";
            if(archiveBtn) archiveBtn.style.color = "#ef4444";
        }

        // Obsługa klas active
        filterButtons.forEach(btn => btn.classList.remove('active'));
        this.classList.add('active');

        // Pobierz kategorię z tekstu przycisku
        currentCategory = this.innerText; 
        
        // Pobierz dane (backend zwróci aktualne wydarzenia)
        fetchEvents(search.value);
    });
});

// Kliknięcie w ARCHIWUM (tylko dla admina)
if (archiveBtn) {
    archiveBtn.addEventListener('click', function() {
        // Włącz tryb archiwum
        isArchiveMode = true;
        
        // Deaktywuj zwykłe filtry wizualnie
        filterButtons.forEach(btn => btn.classList.remove('active'));
        
        // Aktywuj przycisk archiwum (stylowanie na czerwono)
        this.classList.add('active');
        this.style.backgroundColor = "#ef4444";
        this.style.color = "white";

        // Pobierz dane (backend zwróci stare wydarzenia)
        fetchEvents(search.value);
    });
}

function renderEvents(events) {
    eventContainer.innerHTML = "";

    // Filtrowanie po kategorii (client-side)
    // Uwaga: W trybie archiwum też można filtrować po kategorii, jeśli chcemy
    const filteredEvents = events.filter(event => {
        if (currentCategory === 'All' || isArchiveMode) return true; // W archiwum pokazujemy wszystko jak leci
        return event.category === currentCategory;
    });

    if (filteredEvents.length === 0) {
        const msg = isArchiveMode ? "No archived events found." : "No upcoming events.";
        eventContainer.innerHTML = `<p style="color: #94A3B8; grid-column: 1/-1; text-align: center; margin-top: 20px;">${msg}</p>`;
        return;
    }

    filteredEvents.forEach(event => {
        createEventCard(event);
    });
}

function createEventCard(event) {
    // Logika przycisków (Admin vs User)
    let adminActions = '';
    if (userRole === 'uni_admin') {
        adminActions = `
            <div class="card-actions">
                <a href="/edit-event?id=${event.id}" class="icon-btn edit">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                </a>
                <a href="/delete-event?id=${event.id}" class="icon-btn delete" onclick="return confirm('Delete?');">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                </a>
            </div>
        `;
    } else if (userRole === 'user' && !isArchiveMode) {
        // Student widzi Join/Leave tylko w aktualnych wydarzeniach
        if (event.is_joined) {
            adminActions = `<a href="/leave-event?id=${event.id}" class="join-btn leave">Leave</a>`;
        } else {
            adminActions = `<a href="/join-event?id=${event.id}" class="join-btn join">Join</a>`;
        }
    }

    let imageContent = event.image 
        ? `<img src="/public/uploads/${event.image}" alt="Event" style="width:100%; height:100%; object-fit:cover;">`
        : `<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>`;

    // Dodanie klasy 'grayscale' dla archiwum (wizualny efekt - opcjonalne, ale fajne)
    const cardStyle = isArchiveMode ? 'filter: grayscale(100%); opacity: 0.8;' : '';

    const template = `
        <div class="event-card" style="position: relative; ${cardStyle}">
            ${adminActions}
            <div class="card-image">
                ${imageContent}
            </div>
            <div class="card-details">
                <h3>${event.title}</h3>
                <p class="event-date">${event.date}</p>
                <p style="font-size: 0.8rem; color: #64748B; margin: 5px 0;">
                    ${event.location}
                </p>
            </div>
        </div>
    `;
    eventContainer.innerHTML += template;
}