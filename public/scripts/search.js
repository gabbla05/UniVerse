const search = document.querySelector('input[placeholder="Search"]');
const eventContainer = document.querySelector('.events-grid');
const filterButtons = document.querySelectorAll('.filter-btn');

let currentCategory = 'All'; 
let allEventsData = []; 

function fetchEvents(query = "") {
    const data = {search: query};

    fetch("/search", {
        method: "POST",
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(events => {
        allEventsData = events; 
        renderEvents(events);
    })
    .catch(error => console.error('Error fetching events:', error));
}

document.addEventListener("DOMContentLoaded", () => {
    fetchEvents(""); 
});

search.addEventListener("keyup", function (event) {
    if (event.key === "Enter") {
        event.preventDefault();
    }
    fetchEvents(this.value);
});

filterButtons.forEach(button => {
    button.addEventListener('click', function() {
        filterButtons.forEach(btn => btn.classList.remove('active'));
        this.classList.add('active');

        currentCategory = this.innerText; 
        renderEvents(allEventsData);
    });
});

function renderEvents(events) {
    eventContainer.innerHTML = "";

    const filteredEvents = events.filter(event => {
        if (currentCategory === 'All') return true;
        return event.category === currentCategory;
    });

    if (filteredEvents.length === 0) {
        eventContainer.innerHTML = '<p style="color: #94A3B8; grid-column: 1/-1; text-align: center; margin-top: 20px;">No events found.</p>';
        return;
    }

    filteredEvents.forEach(event => {
        createEventCard(event);
    });
}

function createEventCard(event) {
    // Generujemy przyciski tylko jeśli user jest adminem uczelni
    let adminActions = '';
    if (userRole === 'uni_admin') {
        adminActions = `
            <div class="card-actions">
                <a href="/edit-event?id=${event.id}" class="icon-btn edit">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                </a>
                <a href="/delete-event?id=${event.id}" class="icon-btn delete" onclick="return confirm('Are you sure you want to delete this event?');">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                </a>
            </div>
        `;
    }

    // Obsługa braku zdjęcia (placeholder)
    let imageContent = '';
    if (event.image) {
        imageContent = `<img src="/public/uploads/${event.image}" alt="Event" style="width:100%; height:100%; object-fit:cover;">`;
    } else {
        imageContent = `<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>`;
    }

    const template = `
        <div class="event-card" style="position: relative;">
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