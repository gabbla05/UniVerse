const search = document.querySelector('input[placeholder="Search"]');
const eventContainer = document.querySelector('.events-grid');
const filterSelect = document.querySelector('.filters');
const dropdownBtn = document.querySelector('.dropdown-btn');
const dropdownContent = document.querySelector('.dropdown-content');

let currentCategory = 'All'; 
let isArchiveMode = false;

function fetchEvents(query = "") {
    const data = {
        search: query,
        isArchive: isArchiveMode,
        filter: currentCategory 
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

if (filterSelect) {
    filterSelect.addEventListener('change', function() {
        const value = this.value;
        if (value === 'Archive') {
            isArchiveMode = true;
            currentCategory = 'All';
        } else {
            isArchiveMode = false;
            currentCategory = value;
        }
        fetchEvents(search.value);
    });
}

if (dropdownBtn && dropdownContent) {
    dropdownBtn.addEventListener('click', (e) => {
        e.preventDefault();
        dropdownContent.style.display = dropdownContent.style.display === 'block' ? 'none' : 'block';
    });
}

document.addEventListener("DOMContentLoaded", () => {
    fetchEvents(""); 
});

search.addEventListener("keyup", function (event) {
    if (event.key === "Enter") event.preventDefault();
    fetchEvents(this.value);
});

if (filterSelect) {
    filterSelect.addEventListener('change', function() {
        const value = this.value;
        if (value === 'Archive') {
            isArchiveMode = true;
        } else {
            isArchiveMode = false;
            currentCategory = value;
        }
        fetchEvents(search.value);
    });
}

function renderEvents(events) {
    eventContainer.innerHTML = "";

    const filteredEvents = events.filter(event => {
        if (currentCategory === 'All' || isArchiveMode) return true;
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
    let adminActions = '';
    
    if (typeof userRole !== 'undefined' && userRole === 'uni_admin') {
        adminActions = `
            <div class="card-actions" style="z-index: 20;" onclick="event.stopPropagation();">
                <a href="/event-participants?id=${event.id}" class="icon-btn" title="Participants List" style="margin-right: 5px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                </a>

                <a href="/edit-event?id=${event.id}" class="icon-btn edit">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                </a>
                <a href="/delete-event?id=${event.id}" class="icon-btn delete" onclick="return confirm('Delete?');">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                </a>
            </div>
        `;
    }

    let imageContent = event.image 
        ? `<img src="/public/uploads/${event.image}" alt="Event" style="width:100%; height:100%; object-fit:cover;">`
        : `<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>`;

    const cardStyle = isArchiveMode ? 'filter: grayscale(100%); opacity: 0.8;' : '';

    const template = `
        <div class="event-card" style="position: relative; ${cardStyle}">
            ${adminActions}
            
            <a href="/event?id=${event.id}" class="card-link" style="text-decoration: none; color: inherit; display: block; height: 100%;">
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
            </a>
        </div>
    `;
    eventContainer.innerHTML += template;
}