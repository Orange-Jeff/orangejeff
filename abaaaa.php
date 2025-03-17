/* ========== ROOT VARIABLES ========== */
:root {
--primary-color: #0056b3;
--secondary-color: #f8f9fa;
--text-color: #333;
--background-color: #e9ecef;
--header-height: 40px;
--menu-width: 250px;
--button-padding-y: 6px;
--button-padding-x: 10px;
--button-border-radius: 4px;
--status-bar-padding: 8px 15px;
--status-bar-margin: 10px 0;
--success-color: #28a745;
--error-color: #dc3545;
--info-color: #17a2b8;
--transition-speed: 0.3s;
}

/* ========== BASE STYLES ========== */
html, body {
width: 100%;
height: 100%;
margin: 0;
padding: 0;
font-family: Arial, sans-serif;
background-color: #e9ecef;
flex: 1;
overflow: hidden;
}

/* NetBound Tool Suite - Main CSS */
body {
padding: 0;
margin: 0;
font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
background: #e8e8ed; /* Slightly darker gray background */
width: 100%;
height: 100vh;
overflow: hidden;
display: block; /* Override any flex display on body */
}

/* Fixed positioning for standalone and iframe modes */
body {
padding: 0;
margin: 0;
font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
background: #e8e8ed;
width: 100%;
height: 100vh;
overflow: hidden;
display: block; /* Override any flex display on body */
}

/* Remove centering from standalone mode */
body.standalone {
display: block;
justify-content: normal;
}

/* In iframe, use left alignment */
body.in-iframe {
margin: 0;
}

/* ========== LAYOUT ========== */
/* Standardized header styling */
.header {
background: #0056b3;
color: white;
padding: 8px 15px;
height: 50px;
display: flex;
justify-content: space-between;
align-items: center;
width: 100%;
}

.header .left-section,
.header .right-section {
display: flex;
align-items: center;
gap: 10px;
}

.header .left-section {
padding-left: 50px;
align-items: center;
}

.header .left-section .menu-title,
.header .right-section .editor-title {
font-size: 20px;
font-weight: bold;
line-height: 1;
}

.menu-title {
margin: 0;
font-size: 18px;
font-weight: bold;
}

/* Standardized tool title */
.tool-title {
margin: 10px 0; /* Remove extra spacing */
padding: 0;
color: #0056b3;
line-height: 1.2;
font-weight: bold;
font-size: 18px;
}

.header .right-section button,
.header .left-section .header-button {
background-color: var(--secondary-color);
color: var(--primary-color);
border: none;
padding: 4px 8px;
border-radius: var(--button-border-radius);
cursor: pointer;
font-size: 14px;
margin-left: 0;
}

.header .right-section button:hover,
.header .left-section .header-button:hover {
background-color: #e0e0e0;
}

.header .left-section .header-button.disabled {
background-color: #ccc;
color: #666;
cursor: not-allowed;
}

/* Make sure all menu items are visible */
.header-button {
display: inline-flex !important;
align-items: center;
justify-content: center;
margin-right: 5px;
}

/* Menu Layout */
.menu {
width: 250px;
min-width: 250px;
background: #fdfdfd;
border-right: 1px solid #ddd;
overflow-y: auto;
height: 100%;
flex-shrink: 0; /* Prevent shrinking */
}

/* Container layout fixes */
.container {
display: flex;
height: calc(100vh - 50px);
background: #e8e8ed;
width: 100%;
max-width: 100%;
overflow: hidden;
margin: 0; /* Override any margin */
justify-content: flex-start; /* Force left-justification */
}

/* Override any container margin and centering in all modes */
body.standalone .container,
body.in-iframe .container,
body:not(.in-iframe) .container {
margin: 0;
max-width: none;
justify-content: flex-start;
}

.menu-content {
padding: 10px;
}

.menu-overlay {
display: none !important;
}

/* Set editor container to exactly 768px */
.menu-container {
flex: 0 0 768px; /* Fixed width, no grow, no shrink */
display: flex;
flex-direction: column;
overflow: hidden;
width: 768px;
max-width: 768px;
}

/* Make sure editor uses full container width */
.editor-view {
width: 100%;
}

/* Black background for editor */
#editor {
background: #2a2a2a;
}

/* Container Layout */
.container {
display: flex;
height: calc(100vh - 50px);
background: #e8e8ed;
width: 100%;
max-width: 100%;
overflow: hidden;
margin: 0; /* Override any margin */
}

/* Override any container margin in all modes */
body.standalone .container,
body.in-iframe .container,
body:not(.in-iframe) .container {
margin: 0;
max-width: none;
}

.menu-toggle {
display: none !important;
}

/* ========== FOLDER & BREADCRUMB STYLES ========== */
.folder-entry {
background-color: #f8f9fa;
border-radius: 4px;
margin-bottom: 2px;
}

.folder-entry:hover {
background-color: #e9ecef;
}

.folder-normal {
color: #b8860b !important;
}

.folder-special {
color: #007bff !important;
}

.breadcrumb {
display: flex;
align-items: center;
gap: 8px;
padding: 8px 15px;
margin: 0 -15px 15px -15px;
background-color: #f8f9fa;
border-bottom: 1px solid #ddd;
font-size: 14px;
}

.breadcrumb a {
color: var(--primary-color);
text-decoration: none;
}

.breadcrumb a:hover {
text-decoration: underline;
}

.up-level {
margin-left: -5px;
padding: 5px;
color: var(--primary-color);
cursor: pointer;
text-decoration: none;
}

.up-level:hover {
background-color: rgba(0, 0, 0, 0.05);
border-radius: 4px;
}

.folder-name {
color: var(--primary-color);
font-weight: bold;
}

.breadcrumb .separator {
color: #6c757d;
}

/* Breadcrumb navigation */
.breadcrumb {
display: flex;
flex-wrap: wrap;
margin: 0 0 10px 0;
padding: 5px;
background: #f0f0f0;
border-radius: 4px;
}

.breadcrumb a {
text-decoration: none;
color: #0056b3;
}

.separator {
margin: 0 5px;
color: #666;
}

/* ========== FILE LIST STYLES ========== */
.file-list {
list-style: none;
padding: 0;
margin: 0;
}

.file-entry {
display: flex;
align-items: center;
padding: 5px;
border-bottom: 1px solid #eee;
}

.file-entry:hover {
background: #f0f0f0;
}

.file-entry.current-edit {
background: #e3f2fd;
}

.file-controls {
margin-right: 8px;
}

.file-controls button,
.file-controls a {
background: none;
border: none;
color: var(--primary-color);
font-size: 14px;
cursor: pointer;
padding: 2px;
}

.file-controls button:hover,
.file-controls a:hover {
color: #003d82;
}

.select-control {
margin-left: auto;
}

.delete-check {
width: 16px;
height: 16px;
cursor: pointer;
}

.filename {
flex: 1;
text-decoration: none;
color: #333;
white-space: nowrap;
overflow: hidden;
text-overflow: ellipsis;
}

.folder-controls {
display: flex;
gap: 4px;
}

.empty-folder-message {
text-align: center;
padding: 20px;
color: #666;
}

/* ========== FILE TYPE COLORS ========== */
.file-nb {
color: #4287f5 !important;
font-weight: bold;
}

.file-php {
color: #9c27b0 !important;
}

.file-html {
color: #e91e63 !important;
}

.file-css {
color: #2196f3 !important;
}

.file-js {
color: #ffc107 !important;
}

.file-json {
color: #8bc34a !important;
}

.file-txt {
color: #607d8b !important;
}

.file-other {
color: #9e9e9e !important;
}

/* Color coding for file types */
.file-php {
color: #8892bf;
}

.file-html {
color: #e44d26;
}

.file-css {
color: #264de4;
}

.file-js {
color: #f0db4f;
background-color: rgba(240, 219, 79, 0.1);
}

.file-nb {
color: #ff6600;
font-weight: bold;
}

.file-json {
color: #00529b;
}

.folder-normal {
color: #0056b3;
}

.folder-special {
color: #9c27b0;
}

/* Legacy file coloring classes */
.nb-file {
color: #007bff;
font-weight: bold;
}

.php-file {
color: #6f42c1;
}

.js-file {
color: #e9b64d;
}

.css-file {
color: #20c997;
}

.html-file {
color: #e34c26;
}

.other-file {
color: #6c757d;
}

/* ========== EDITOR STYLES ========== */
.editor {
display: flex;
flex-direction: column;
height: 100%;
overflow: hidden;
}

.editor.fullscreen {
max-width: 100%;
width: 100%;
box-sizing: border-box;
}

.editor-container {
flex: 1;
overflow: hidden;
border: 1px solid #555;
border-radius: 4px;
}

.editor-container.fullwidth {
width: calc(100% - 40px);
max-width: calc(100% - 40px) !important;
margin-left: -20px;
margin-right: -20px;
padding: 0 20px;
z-index: 5;
background-color: #272822;
border-radius: 5px;
box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
box-sizing: border-box;
}

#editor {
width: 100%;
height: 100%;
background: #2a2a2a;
}

.editor-header {
margin-bottom: 5px; /* Reduced margin to match template spacing */
}

.editor-title {
margin: 10px 0;
padding: 0;
color: #0056b3;
line-height: 1.2;
font-weight: bold;
font-size: 18px;
}

.header-top {
display: flex;
justify-content: space-between;
align-items: center;
}

.editor-buttons {
display: flex;
gap: 10px;
justify-content: flex-end;
align-items: center;
}

.edit-form {
display: flex;
flex-direction: column;
height: 100%;
min-height: 220px;
}

.label-line {
display: flex;
gap: 10px;
margin: 10px 0;
align-items: center;
}

.info-label {
color: var(--text-color);
font-weight: normal;
width: 80px;
font-size: 14px;
}

.info-input {
flex: 1;
padding: 6px 8px;
border: 1px solid #ddd;
border-radius: 3px;
font-family: inherit;
font-size: 14px;
}

.editor-nav-controls {
position: absolute;
right: 25px;
top: 15px;
display: flex;
flex-direction: row;
z-index: 1000;
background-color: rgba(50, 50, 50, 0.8);
border-radius: 4px;
padding: 2px;
}

.editor-nav-controls button {
margin: 2px;
width: 34px;
height: 34px;
border: none;
border-radius: 4px;
background-color: rgba(80, 80, 80, 9);
color: #ffffff;
cursor: pointer;
display: flex;
align-items: center;
justify-content: center;
transition: background-color 0.2s;
box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
}

.editor-nav-controls button:hover {
background-color: #0078d7;
}

/* ========== BUTTON STYLES ========== */
.button-row {
display: flex;
justify-content: space-between;
margin: 10px 0;
padding: 0;
}

.button-group {
display: flex;
gap: 10px;
margin: 10px 0;
padding: 0;
flex-wrap: nowrap;
}

.command-button,
.split-button {
font-size: 14px;
padding: var(--button-padding-y) var(--button-padding-x);
background-color: var(--primary-color);
color: white;
border: none;
cursor: pointer;
border-radius: var(--button-border-radius);
display: inline-flex;
align-items: center;
gap: 5px;
}

.header-button, .command-button {
background: #0056b3;
color: white;
border: none;
border-radius: 3px;
padding: 6px 8px;
cursor: pointer;
font-size: 14px;
display: inline-flex;
align-items: center;
gap: 4px;
}

.command-button:hover,
.split-button:hover {
background-color: #003d82;
}

.header-button:hover, .command-button:hover {
background: #004494;
}

.split-button {
display: flex;
overflow: hidden;
width: fit-content;
}

.split-button .main-part,
.split-button .append-part {
background-color: var(--primary-color);
padding: var(--button-padding-y) var(--button-padding-x);
display: flex;
align-items: center;
cursor: pointer;
}

.main-part {
background: #0056b3;
color: white;
border: none;
padding: 6px 8px;
cursor: pointer;
font-size: 14px;
display: inline-flex;
align-items: center;
gap: 4px;
border-top-left-radius: 3px;
border-bottom-left-radius: 3px;
}

.split-button .main-part {
border-radius: var(--button-border-radius) 0 0 var(--button-border-radius);
}

.split-button .append-part {
padding: var(--button-padding-y) 7px;
border-radius: 0 var(--button-border-radius) var(--button-border-radius) 0;
display: flex;
align-items: center;
justify-content: center;
}

.append-part {
background: #0056b3;
color: white;
border: none;
border-left: 1px solid rgba(255, 255, 255, 0.2);
padding: 6px 8px;
cursor: pointer;
font-size: 14px;
border-top-right-radius: 3px;
border-bottom-right-radius: 3px;
}

.split-button i {
margin-right: 5px;
}

.split-button .main-part:hover,
.split-button .append-part:hover {
background-color: #003d82;
}

.main-part:hover, .append-part:hover {
background: #004494;
}

.header-button:disabled, .command-button:disabled {
background: #ccc;
cursor: not-allowed;
}

.main-part:disabled, .append-part:disabled {
background: #ccc;
cursor: not-allowed;
}

/* ========== STATUS BAR STYLES ========== */
.persistent-status-bar {
width: 100%;
height: 90px;
min-height: 90px;
max-height: 90px;
overflow-y: auto;
border: 1px solid #ddd;
background: #fff;
padding: 5px;
margin: 10px 0;
border-radius: 4px;
display: flex;
flex-direction: column-reverse;
box-sizing: border-box;
}

.persistent-status-bar.drag-over {
background: #e3f2fd;
border-color: #2196f3;
border-style: dashed;
}

.status-bar {
width: 100%;
height: 90px;
min-height: 90px;
max-height: 90px;
overflow-y: auto;
border: 1px solid #ddd;
background: #fff;
padding: 5px;
margin: 10px 0;
border-radius: 4px;
display: flex;
flex-direction: column-reverse;
box-sizing: border-box;
}

.status-message {
padding: 5px;
margin: 2px 0;
border-radius: 3px;
color: #666;
}

.status-message:first-child {
color: white;
}

.persistent-status-bar .status-message:first-child.success,
.status-message.success:first-child {
background-color: var(--success-color);
color: white;
}

.persistent-status-bar .status-message:first-child.error,
.status-message.error:first-child {
background-color: var(--error-color);
color: white;
}

.persistent-status-bar .status-message:first-child.info,
.status-message.info:first-child {
background-color: var(--info-color);
color: white;
}

.status-message.info {
border-left: 3px solid #2196f3;
}

.status-message.info:first-child {
background: #2196f3;
}

.status-message.success {
border-left: 3px solid #4caf50;
}

.status-message.success:first-child {
background: #4caf50;
}

.status-message.error {
border-left: 3px solid #f44336;
}

.status-message.error:first-child {
background: #f44336;
}

.status-message.warning {
border-left: 3px solid #ff9800;
}

.status-message.warning:first-child {
background: #ff9800;
}

/* ========== VIEW STYLES ========== */
.editor-view,
.backup-view {
display: flex;
flex-direction: column;
width: 100%;
height: 100%;
position: absolute;
top: var(--header-height);
left: var(--menu-width);
right: 0;
transition: transform 0.3s ease-in-out, left 0.3s ease, visibility 0.3s;
visibility: visible;
z-index: 10;
}

.editor-view.hidden {
visibility: hidden !important;
z-index: -1 !important;
}

.editor-view.hidden .editor-container,
.editor-view.hidden .editor-container * {
visibility: hidden !important;
}

.backup-view {
z-index: 5;
visibility: hidden;
}

/* For iframe mode */
body.in-iframe .container {
margin: 0;
}

/* For standalone mode */
body:not(.in-iframe) .container {
margin: 0 auto;
max-width: 1280px;
}

.backup-view.active {
z-index: 20;
visibility: visible;
}

.backup-view {
display: none;
height: 100%;
}

.backup-view.active {
display: block;
}

/* ========== RESPONSIVE STYLES ========== */
@media (min-height: 800px) {
.button-row {
padding-bottom: 20px;
margin-bottom: 15px;
}
}

@media (max-height: 700px) {
.editor {
height: calc(100vh - 220px);
}

.editor-container {
height: calc(100vh - 300px);
}
}

@media (max-height: 600px) {
.editor {
padding: 5px;
}

.editor-container {
min-height: 80px;
max-height: calc(100vh - var(--header-height) - 180px);
height: calc(100vh - var(--header-height) - 140px);
}

.button-group {
margin-top: 2px;
}

.persistent-status-bar {
max-height: 60px;
}

.button-row .command-button,
.button-row .split-button {
padding: 4px 8px;
font-size: 12px;
}
}

@media (max-height: 500px) {
.editor-container {
height: calc(100vh - var(--header-height) - 100px);
}
}

@media (min-height: 601px) and (max-height: 800px) {
.editor-container {
max-height: calc(100vh - var(--header-height) - 200px);
}
}

@media (max-width: 768px) {
.menu {
transform: none !important;
width: var(--menu-width);
}

.container {
margin-left: var(--menu-width);
width: calc(100% - var(--menu-width));
}

.editor {
padding: 15px;
}

#menuPanel {
height: calc(100vh - var(--header-height));
overflow-y: auto;
-webkit-overflow-scrolling: touch;
}
}

/* Mobile responsiveness fixes */
@media screen and (max-width: 768px) {
/* Keep left padding on mobile */
.left-section {
padding-left: 10px;
}

/* Mobile styles */
.menu {
height: auto;
max-height: calc(100vh - 60px);
overflow-y: auto;
-webkit-overflow-scrolling: touch;
}

.container {
height: calc(100vh - 60px);
overflow: hidden;
}

.editor-container {
height: auto !important;
min-height: 200px;
max-height: calc(100vh - 200px);
}

#editor {
height: 50vh !important;
}

.menu-container {
height: auto;
max-height: calc(100vh - 60px);
overflow-y: auto;
}
}

/* Additional styles to adjust the header layout */
.menu-title {
margin-right: 40px;
padding-left: 0;
}

.left-section {
display: flex;
align-items: center;
padding-left: 50px;
}

/* Hidden elements */
.hidden {
display: none !important;
}

/* Add box-sizing to all elements */
* {
box-sizing: border-box;
}
