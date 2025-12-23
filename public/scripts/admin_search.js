const searchInput = document.getElementById('uni-search');
const tableBody = document.getElementById('uni-table-body');

searchInput.addEventListener("keyup", function (event) {
    if (event.key === "Enter") {
        event.preventDefault();
    }
    
    const data = { search: this.value };

    fetch("/search-universities", {
        method: "POST",
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(universities => {
        tableBody.innerHTML = ""; // Czyścimy tabelę
        
        if (universities.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="6" style="text-align:center; color: #94A3B8; padding: 2rem;">No universities found.</td></tr>';
            return;
        }

        universities.forEach(uni => {
            createUniversityRow(uni);
        });
    })
    .catch(error => console.error('Error:', error));
});

function createUniversityRow(uni) {
    // 1. Budowanie listy wydziałów
    let facultiesHtml = '';
    if (!uni.faculties || uni.faculties.length === 0) {
        facultiesHtml = '<span style="color: var(--text-gray); font-size: 0.8rem;">No faculties</span>';
    } else {
        let listItems = uni.faculties.map(f => `<li>${f}</li>`).join('');
        facultiesHtml = `
            <details class="faculty-dropdown">
                <summary>Show faculties (${uni.faculties.length})</summary>
                <ul class="faculty-list">
                    ${listItems}
                </ul>
            </details>
        `;
    }

    // 2. Szablon wiersza
    const row = `
        <tr>
            <td>${uni.id}</td>
            <td><strong>${uni.name}</strong></td>
            <td>${uni.city}</td>
            <td>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <div style="width: 24px; height: 24px; background: var(--accent-neon); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--bg-dark); font-size: 10px; font-weight: bold;">
                        A
                    </div>
                    ${uni.admin_name}
                </div>
            </td>
            <td>${facultiesHtml}</td>
            <td>
                <div style="display: flex; gap: 8px;">
                    <a href="/edit-university?id=${uni.id}" class="action-btn edit" title="Edit">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                    </a>
                    <a href="/delete-university?id=${uni.id}" class="action-btn delete" title="Delete" onclick="return confirm('Are you sure? This will delete the university and its events/faculties!');">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                    </a>
                </div>
            </td>
        </tr>
    `;

    tableBody.innerHTML += row;
}