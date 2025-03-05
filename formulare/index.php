<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sběrač dat z kontaktních formulářů - Firebase</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        header {
            background-color: #4a6da7;
            color: white;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        h1 {
            margin: 0;
        }
        .container {
            display: flex;
            gap: 20px;
        }
        .forms-panel {
            flex: 1;
            background-color: #f5f5f5;
            padding: 20px;
            border-radius: 5px;
        }
        .data-panel {
            flex: 2;
            background-color: #f5f5f5;
            padding: 20px;
            border-radius: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #4a6da7;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input, select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        button {
            background-color: #4a6da7;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #3a5a8f;
        }
        .filter-bar {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }
        .filter-bar input, .filter-bar select {
            flex: 1;
        }
        .filter-bar button {
            flex: 0 0 auto;
        }
        .actions {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        .success {
            background-color: #dff0d8;
            color: #3c763d;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
            display: none;
        }
        .loading {
            display: none;
            text-align: center;
            padding: 20px;
        }
        .loading:after {
            content: " .";
            animation: dots 1s steps(5, end) infinite;
        }
        @keyframes dots {
            0%, 20% { color: rgba(0,0,0,0); text-shadow: 0.25em 0 0 rgba(0,0,0,0), 0.5em 0 0 rgba(0,0,0,0); }
            40% { color: #333; text-shadow: 0.25em 0 0 rgba(0,0,0,0), 0.5em 0 0 rgba(0,0,0,0); }
            60% { text-shadow: 0.25em 0 0 #333, 0.5em 0 0 rgba(0,0,0,0); }
            80%, 100% { text-shadow: 0.25em 0 0 #333, 0.5em 0 0 #333; }
        }
    </style>
</head>
<body>
    <header>
        <h1>Sběrač dat z kontaktních formulářů - Firebase</h1>
    </header>

    <div class="container">
        <div class="forms-panel">
            <h2>Přidat nový formulář</h2>
            <div class="form-group">
                <label for="form-name">Název formuláře</label>
                <input type="text" id="form-name" placeholder="Např. Kontaktní formulář na homepage">
            </div>
            <div class="form-group">
                <label for="website-url">URL webu</label>
                <input type="url" id="website-url" placeholder="https://mujweb.cz">
            </div>
            <div class="form-group">
                <label for="form-fields">Pole formuláře</label>
                <input type="text" id="form-fields" placeholder="jméno,email,telefon,zpráva">
                <small>Zadejte názvy polí oddělené čárkou</small>
            </div>
            <button id="add-form">Přidat formulář</button>
            
            <div class="success" id="form-success">Formulář byl úspěšně přidán!</div>
            
            <h3 style="margin-top: 30px;">Vaše formuláře</h3>
            <div id="loading-forms" class="loading">Načítání formulářů</div>
            <ul id="forms-list">
                <li>Zatím nemáte žádné formuláře</li>
            </ul>
        </div>
        
        <div class="data-panel">
            <h2>Přijatá data</h2>
            
            <div class="filter-bar">
                <input type="text" id="search-data" placeholder="Hledat...">
                <select id="filter-form">
                    <option value="">Všechny formuláře</option>
                </select>
                <select id="filter-date">
                    <option value="">Všechna data</option>
                    <option value="today">Dnes</option>
                    <option value="week">Tento týden</option>
                    <option value="month">Tento měsíc</option>
                </select>
                <button id="apply-filters">Filtrovat</button>
            </div>
            
            <div id="loading-submissions" class="loading">Načítání dat</div>
            <table>
                <thead>
                    <tr>
                        <th>Datum</th>
                        <th>Formulář</th>
                        <th>Data</th>
                        <th>Akce</th>
                    </tr>
                </thead>
                <tbody id="submissions-data">
                    <tr>
                        <td colspan="4" style="text-align: center;">Zatím nejsou žádná data</td>
                    </tr>
                </tbody>
            </table>
            
            <div class="actions">
                <button id="export-csv">Exportovat CSV</button>
                <button id="clear-data">Vymazat data</button>
            </div>
        </div>
    </div>

    <!-- Firebase SDK -->
    <script type="module">
        // Importy Firebase modulů
        import { initializeApp } from "https://www.gstatic.com/firebasejs/10.9.0/firebase-app.js";
        import { getFirestore, collection, addDoc, getDocs, doc, deleteDoc, query, where, orderBy, Timestamp } from "https://www.gstatic.com/firebasejs/10.9.0/firebase-firestore.js";

        // Konfigurace Firebase - NAHRAĎTE VLASTNÍMI ÚDAJI z Firebase konzole!
        const firebaseConfig = {
            apiKey: "VAŠE_API_KEY",
            authDomain: "VÁŠ_PROJECT_ID.firebaseapp.com",
            projectId: "VÁŠ_PROJECT_ID",
            storageBucket: "VÁŠ_PROJECT_ID.appspot.com",
            messagingSenderId: "VÁŠ_SENDER_ID",
            appId: "VAŠE_APP_ID"
        };

        // Inicializace Firebase
        const app = initializeApp(firebaseConfig);
        const db = getFirestore(app);

        // Globální proměnné
        let forms = [];
        let submissions = [];

        // Načtení formulářů při startu
        document.addEventListener('DOMContentLoaded', function() {
            loadForms();
            loadSubmissions();
        });

        // Funkce pro načtení formulářů z Firebase
        async function loadForms() {
            document.getElementById('loading-forms').style.display = 'block';
            
            try {
                const formsQuery = query(collection(db, "forms"), orderBy("createdAt", "desc"));
                const querySnapshot = await getDocs(formsQuery);
                
                forms = [];
                querySnapshot.forEach((doc) => {
                    forms.push({
                        id: doc.id,
                        ...doc.data()
                    });
                });
                
                updateFormsList();
                updateFormsDropdown();
            } catch (error) {
                console.error("Chyba při načítání formulářů:", error);
                alert("Nastala chyba při načítání formulářů: " + error.message);
            } finally {
                document.getElementById('loading-forms').style.display = 'none';
            }
        }

        // Aktualizace seznamu formulářů
        function updateFormsList() {
            const list = document.getElementById('forms-list');
            if (forms.length === 0) {
                list.innerHTML = '<li>Zatím nemáte žádné formuláře</li>';
                return;
            }
            
            list.innerHTML = '';
            forms.forEach(form => {
                const li = document.createElement('li');
                li.innerHTML = `<strong>${form.name}</strong> (${form.url}) - ID: <code>${form.id}</code>`;
                
                // Přidání tlačítka pro smazání
                const deleteBtn = document.createElement('button');
                deleteBtn.textContent = 'Smazat';
                deleteBtn.style.marginLeft = '10px';
                deleteBtn.style.padding = '3px 6px';
                deleteBtn.style.fontSize = '12px';
                deleteBtn.style.backgroundColor = '#d9534f';
                deleteBtn.onclick = function() {
                    deleteForm(form.id);
                };
                
                li.appendChild(deleteBtn);
                list.appendChild(li);
            });
        }

        // Aktualizace dropdown filtru formulářů
        function updateFormsDropdown() {
            const select = document.getElementById('filter-form');
            // Zachová první option
            select.innerHTML = '<option value="">Všechny formuláře</option>';
            
            forms.forEach(form => {
                const option = document.createElement('option');
                option.value = form.id;
                option.textContent = form.name;
                select.appendChild(option);
            });
        }

        // Přidání nového formuláře
        document.getElementById('add-form').addEventListener('click', async function() {
            const name = document.getElementById('form-name').value;
            const url = document.getElementById('website-url').value;
            const fields = document.getElementById('form-fields').value.split(',').map(f => f.trim());
            
            if (!name || !url || fields.length === 0) {
                alert('Vyplňte prosím všechna pole');
                return;
            }
            
            const newForm = {
                name: name,
                url: url,
                fields: fields,
                createdAt: Timestamp.now()
            };
            
            try {
                // Přidání do Firebase
                const docRef = await addDoc(collection(db, "forms"), newForm);
                
                // Reset formuláře
                document.getElementById('form-name').value = '';
                document.getElementById('website-url').value = '';
                document.getElementById('form-fields').value = '';
                
                // Znovu načíst formuláře
                await loadForms();
                
                // Zobrazení potvrzení
                const success = document.getElementById('form-success');
                success.style.display = 'block';
                setTimeout(() => {
                    success.style.display = 'none';
                }, 3000);
            } catch (error) {
                console.error("Chyba při přidávání formuláře:", error);
                alert("Nastala chyba při přidávání formuláře: " + error.message);
            }
        });

        // Smazání formuláře
        async function deleteForm(formId) {
            if (confirm('Opravdu chcete smazat tento formulář? Všechna související data budou také smazána.')) {
                try {
                    // Smazání formuláře
                    await deleteDoc(doc(db, "forms", formId));
                    
                    // Smazání souvisejících dat (submissions)
                    const submissionsQuery = query(collection(db, "submissions"), where("formId", "==", formId));
                    const submissionsSnapshot = await getDocs(submissionsQuery);
                    
                    const deletePromises = [];
                    submissionsSnapshot.forEach((submissionDoc) => {
                        deletePromises.push(deleteDoc(doc(db, "submissions", submissionDoc.id)));
                    });
                    
                    await Promise.all(deletePromises);
                    
                    // Znovu načíst data
                    await loadForms();
                    await loadSubmissions();
                } catch (error) {
                    console.error("Chyba při mazání formuláře:", error);
                    alert("Nastala chyba při mazání formuláře: " + error.message);
                }
            }
        }

        // Načtení přijatých dat
        async function loadSubmissions() {
            document.getElementById('loading-submissions').style.display = 'block';
            
            try {
                const submissionsQuery = query(collection(db, "submissions"), orderBy("timestamp", "desc"));
                const querySnapshot = await getDocs(submissionsQuery);
                
                submissions = [];
                querySnapshot.forEach((doc) => {
                    submissions.push({
                        id: doc.id,
                        ...doc.data()
                    });
                });
                
                updateSubmissionsTable();
            } catch (error) {
                console.error("Chyba při načítání dat:", error);
                alert("Nastala chyba při načítání dat: " + error.message);
            } finally {
                document.getElementById('loading-submissions').style.display = 'none';
            }
        }

        // Aktualizace tabulky s přijatými daty
        function updateSubmissionsTable() {
            const tbody = document.getElementById('submissions-data');
            if (submissions.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" style="text-align: center;">Zatím nejsou žádná data</td></tr>';
                return;
            }
            
            tbody.innerHTML = '';
            
            // Filtrování dat
            let filteredSubmissions = [...submissions];
            const formFilter = document.getElementById('filter-form').value;
            const dateFilter = document.getElementById('filter-date').value;
            const searchFilter = document.getElementById('search-data').value.toLowerCase();
            
            if (formFilter) {
                filteredSubmissions = filteredSubmissions.filter(s => s.formId === formFilter);
            }
            
            if (dateFilter) {
                const now = new Date();
                let startDate;
                
                if (dateFilter === 'today') {
                    startDate = new Date(now.getFullYear(), now.getMonth(), now.getDate());
                } else if (dateFilter === 'week') {
                    const day = now.getDay() || 7;
                    startDate = new Date(now.getFullYear(), now.getMonth(), now.getDate() - day + 1);
                } else if (dateFilter === 'month') {
                    startDate = new Date(now.getFullYear(), now.getMonth(), 1);
                }
                
                if (startDate) {
                    filteredSubmissions = filteredSubmissions.filter(s => {
                        // Převod Firebase Timestamp na JavaScript Date
                        const submissionDate = s.timestamp.toDate();
                        return submissionDate >= startDate;
                    });
                }
            }
            
            if (searchFilter) {
                filteredSubmissions = filteredSubmissions.filter(s => {
                    // Prohledáme název formuláře
                    if (s.formName && s.formName.toLowerCase().includes(searchFilter)) return true;
                    
                    // Prohledáme data formuláře
                    for (const key in s.data) {
                        if (String(s.data[key]).toLowerCase().includes(searchFilter)) return true;
                    }
                    
                    return false;
                });
            }
            
            // Zobrazení dat
            filteredSubmissions.forEach(submission => {
                const tr = document.createElement('tr');
                
                // Datum
                const dateTd = document.createElement('td');
                // Převod Firebase Timestamp na JavaScript Date
                const date = submission.timestamp.toDate();
                dateTd.textContent = date.toLocaleString('cs-CZ');
                tr.appendChild(dateTd);
                
                // Formulář
                const formTd = document.createElement('td');
                // Najdeme název formuláře podle ID
                const form = forms.find(f => f.id === submission.formId);
                formTd.textContent = form ? form.name : submission.formId;
                tr.appendChild(formTd);
                
                // Data
                const dataTd = document.createElement('td');
                let dataHtml = '';
                for (const key in submission.data) {
                    if (key !== 'formId') { // Přeskočíme formId, protože to již zobrazujeme
                        dataHtml += `<strong>${key}:</strong> ${submission.data[key]}<br>`;
                    }
                }
                dataTd.innerHTML = dataHtml;
                tr.appendChild(dataTd);
                
                // Akce
                const actionsTd = document.createElement('td');
                const deleteBtn = document.createElement('button');
                deleteBtn.textContent = 'Smazat';
                deleteBtn.style.backgroundColor = '#d9534f';
                deleteBtn.onclick = function() {
                    deleteSubmission(submission.id);
                };
                actionsTd.appendChild(deleteBtn);
                tr.appendChild(actionsTd);
                
                tbody.appendChild(tr);
            });
        }

        // Mazání jednoho záznamu
        async function deleteSubmission(id) {
            if (confirm('Opravdu chcete smazat tento záznam?')) {
                try {
                    await deleteDoc(doc(db, "submissions", id));
                    await loadSubmissions();
                } catch (error) {
                    console.error("Chyba při mazání záznamu:", error);
                    alert("Nastala chyba při mazání záznamu: " + error.message);
                }
            }
        }

        // Filtrování dat
        document.getElementById('apply-filters').addEventListener('click', function() {
            updateSubmissionsTable();
        });
        
        // Mazání všech dat
        document.getElementById('clear-data').addEventListener('click', async function() {
            if (confirm('Opravdu chcete vymazat všechna data? Tato akce je nevratná.')) {
                try {
                    document.getElementById('loading-submissions').style.display = 'block';
                    
                    const submissionsQuery = query(collection(db, "submissions"));
                    const querySnapshot = await getDocs(submissionsQuery);
                    
                    const deletePromises = [];
                    querySnapshot.forEach((doc) => {
                        deletePromises.push(deleteDoc(doc.ref));
                    });
                    
                    await Promise.all(deletePromises);
                    
                    // Znovu načíst data
                    await loadSubmissions();
                } catch (error) {
                    console.error("Chyba při mazání dat:", error);
                    alert("Nastala chyba při mazání dat: " + error.message);
                } finally {
                    document.getElementById('loading-submissions').style.display = 'none';
                }
            }
        });
        
        // Export do CSV
        document.getElementById('export-csv').addEventListener('click', function() {
            if (submissions.length === 0) {
                alert('Nejsou k dispozici žádná data pro export');
                return;
            }
            
            // Získání filtrovaných dat pro export
            let filteredSubmissions = [...submissions];
            const formFilter = document.getElementById('filter-form').value;
            const dateFilter = document.getElementById('filter-date').value;
            const searchFilter = document.getElementById('search-data').value.toLowerCase();
            
            if (formFilter) {
                filteredSubmissions = filteredSubmissions.filter(s => s.formId === formFilter);
            }
            
            if (dateFilter) {
                const now = new Date();
                let startDate;
                
                if (dateFilter === 'today') {
                    startDate = new Date(now.getFullYear(), now.getMonth(), now.getDate());
                } else if (dateFilter === 'week') {
                    const day = now.getDay() || 7;
                    startDate = new Date(now.getFullYear(), now.getMonth(), now.getDate() - day + 1);
                } else if (dateFilter === 'month') {
                    startDate = new Date(now.getFullYear(), now.getMonth(), 1);
                }
                
                if (startDate) {
                    filteredSubmissions = filteredSubmissions.filter(s => {
                        const submissionDate = s.timestamp.toDate();
                        return submissionDate >= startDate;
                    });
                }
            }
            
            if (searchFilter) {
                filteredSubmissions = filteredSubmissions.filter(s => {
                    if (s.formName && s.formName.toLowerCase().includes(searchFilter)) return true;
                    
                    for (const key in s.data) {
                        if (String(s.data[key]).toLowerCase().includes(searchFilter)) return true;
                    }
                    
                    return false;
                });
            }
            
            // Zjistíme všechna možná pole z dat
            const allFields = new Set();
            filteredSubmissions.forEach(submission => {
                Object.keys(submission.data || {}).forEach(key => {
                    if (key !== 'formId') { // Přeskočíme formId
                        allFields.add(key);
                    }
                });
            });
            
            // Vytvoření CSV
            let csv = 'Datum,Formulář';
            
            // Přidáme hlavičky pro všechna pole
            allFields.forEach(field => {
                csv += `,${field}`;
            });
            
            csv += '\n';
            
            // Přidáme data
            filteredSubmissions.forEach(submission => {
                const date = submission.timestamp.toDate().toLocaleString('cs-CZ');
                const formName = forms.find(f => f.id === submission.formId)?.name || submission.formId;
                
                csv += `"${date}","${formName}"`;
                
                allFields.forEach(field => {
                    const value = submission.data?.[field] || '';
                    csv += `,"${value.toString().replace(/"/g, '""')}"`;
                });
                
                csv += '\n';
            });
            
            // Stažení CSV
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.setAttribute('href', url);
            link.setAttribute('download', `formular-data-${new Date().toISOString().slice(0,10)}.csv`);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        });
    </script>
</body>
</html>
