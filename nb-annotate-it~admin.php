<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NetBound Tools: Project Manager</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #0056b3;
            --secondary-color: #f8f9fa;
            --text-color: #333;
            --background-color: #e9ecef;
            --header-height: 40px;
            --button-padding-y: 6px;
            --button-padding-x: 10px;
            --button-border-radius: 4px;
            --status-bar-padding: 8px 15px;
            --status-bar-margin: 5px 0;
            --success-color: #28a745;
            --error-color: #dc3545;
            --info-color: #17a2b8;
            --transition-speed: 0.3s;
            --warning-color: #f39c12;
            --container-width: 900px;
        }

        * {
            box-sizing: border-box;
            max-width: 100%;
        }

        html,
        body {
            width: 100%;
            height: 100%;
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background-color: var(--background-color);
            text-align: left;
            overflow-x: hidden;
        }

        .tool-container {
            max-width: var(--container-width);
            width: 100%;
            margin: 0 auto;
            padding: 0;
            display: flex;
            flex-direction: column;
            box-sizing: border-box;
            min-height: 100vh;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.07);
            overflow: hidden;
        }

        .tool-header {
            width: 100%;
            display: flex;
            flex-direction: column;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            background: var(--secondary-color);
            border-radius: 8px 8px 0 0;
        }

        .header-flex {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            padding: 0;
            margin-top: 0;
        }

        .tool-title {
            margin: 8px 0;
            padding-left: 10px;
            color: var(--primary-color);
            line-height: 1.2;
            font-weight: bold;
            font-size: 20px;
            flex: 1;
        }

        .header-buttons {
            display: flex;
            gap: 5px;
            align-items: center;
            padding-right: 10px;
        }

        .header-button,
        .hamburger-menu {
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: var(--button-border-radius);
            padding: 4px 8px;
            font-size: 15px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            margin-left: 5px;
            text-decoration: none;
            box-sizing: border-box;
        }

        .header-button:hover,
        .hamburger-menu:hover {
            background-color: #003d82;
        }

        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            padding: 0 10px 10px 10px;
            background: white;
            overflow-x: auto;
            width: 100%;
        }

        .status-box {
            width: 100%;
            min-height: 40px;
            max-height: 90px;
            overflow-y: auto;
            border: 1px solid var(--primary-color);
            background: #fff;
            padding: 8px 10px;
            margin: 10px 0 10px 0;
            border-radius: 4px;
            display: flex;
            flex-direction: column-reverse;
            box-sizing: border-box;
            position: relative;
        }

        .project-list-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 0;
            background: #fff;
            border-radius: 6px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.07);
            overflow: hidden;
            table-layout: fixed;
        }

        .project-list-table th,
        .project-list-table td {
            padding: 14px 10px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
            word-break: break-word;
        }

        .project-list-table th {
            background: var(--primary-color);
            color: #fff;
            font-weight: bold;
        }

        .project-list-table tr:last-child td {
            border-bottom: none;
        }

        .project-actions {
            display: flex;
            gap: 8px;
            justify-content: flex-end;
            flex-wrap: wrap;
        }

        .project-action-btn {
            background: var(--primary-color);
            color: #fff;
            border: none;
            border-radius: 4px;
            padding: 6px 14px;
            font-size: 15px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: background 0.2s;
            text-decoration: none !important;
        }

        .project-action-btn.delete {
            background: var(--error-color);
        }

        .project-action-btn:hover {
            background: #003d82;
        }

        .project-action-btn.delete:hover {
            background: #a71d2a;
        }

        .project-name {
            font-weight: bold;
            color: var(--primary-color);
        }

        .empty-message {
            text-align: center;
            color: #888;
            padding: 30px 0;
            font-size: 18px;
        }

        @media (max-width: 900px) {
            .tool-container {
                max-width: 100%;
                width: 100%;
                border-radius: 0;
            }

            .project-action-btn {
                padding: 6px 10px;
                font-size: 14px;
            }
        }
    </style>
</head>

<body>
    <div class="tool-container">
        <div class="tool-header">
            <div class="header-flex">
                <h1 class="tool-title"><i class="fas fa-folder-open"></i> NetBound Tools: Project Manager</h1>
                <div class="header-buttons">
                    <a href="main.php" class="hamburger-menu" title="Go to Main Menu">
                        <i class="fas fa-bars"></i>
                    </a>
                </div>
            </div>
            <div id="statusBox" class="status-box"></div>
        </div>
        <div class="main-content">
            <table class="project-list-table" id="projectTable">
                <thead>
                    <tr>
                        <th width="35%">Project Name</th>
                        <th width="15%">Images</th>
                        <th width="25%">Last Saved</th>
                        <th width="25%">Actions</th>
                    </tr>
                </thead>
                <tbody id="projectTableBody">
                    <tr>
                        <td colspan="4" class="empty-message">Loading projects...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <script>
        function sanitizeName(name) {
            return name.toLowerCase().replace(/[^a-z0-9]/g, '_');
        }

        function showStatus(msg, type) {
            var box = document.getElementById('statusBox');
            box.innerHTML = '<span style="color:' + (type === 'error' ? '#c00' : type === 'success' ? '#28a745' : '#0056b3') + ';font-weight:bold;">' + msg + '</span>';
            setTimeout(function() {
                box.innerHTML = '';
            }, 3000);
        }

        function loadProjects() {
            fetch('nb-annotate-it~list.php')
                .then(response => response.json())
                .then(data => {
                    var tbody = document.getElementById('projectTableBody');
                    tbody.innerHTML = '';
                    if (!data.success || !data.projects || data.projects.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="4" class="empty-message">No projects found.</td></tr>';
                        return;
                    }
                    data.projects.forEach(function(p) {
                        var sanitized = sanitizeName(p.name);
                        var proofId = p.id.replace('nbproof_', '');
                        var previewUrl = 'nb-annotate-it.php?proof=' + sanitized + '_' + proofId;
                        var tr = document.createElement('tr');
                        tr.innerHTML =
                            '<td class="project-name">' + p.name + '</td>' +
                            '<td>' + p.imageCount + '</td>' +
                            '<td>' + p.lastSaved + '</td>' +
                            '<td class="project-actions">' +
                            '<a href="' + previewUrl + '" target="_blank" class="project-action-btn"><i class="fas fa-eye"></i> Preview</a>' +
                            '<button class="project-action-btn delete" onclick="deleteProject(\'' + p.id + '\', this)"><i class="fas fa-trash"></i> Delete</button>' +
                            '</td>';
                        tbody.appendChild(tr);
                    });
                });
        }

        function deleteProject(id, btn) {
            if (!confirm('Delete this project? This cannot be undone.')) return;
            btn.disabled = true;
            fetch('nb-annotate-it~delete.php?id=' + encodeURIComponent(id))
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        showStatus('Project deleted.', 'success');
                        loadProjects();
                    } else {
                        showStatus(data.error || 'Delete failed.', 'error');
                        btn.disabled = false;
                    }
                });
        }
        document.addEventListener('DOMContentLoaded', loadProjects);
    </script>
</body>

</html>
