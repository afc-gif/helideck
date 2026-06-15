/* global idb */

// Backend API configuration
const API_BASE = "http://localhost:8002/api"; // Update to your backend URL
const FORM_VERSION = "rev15-2023-02-10";

// Check authentication on page load
function checkAuth() {
  const token = localStorage.getItem('auth_token');
  if (!token) {
    window.location.href = '/login.html';
    return false;
  }
  return true;
}

// Get current auth token
function getAuthToken() {
  return localStorage.getItem('auth_token');
}

// Get current user
function getCurrentUser() {
  const userStr = localStorage.getItem('auth_user');
  return userStr ? JSON.parse(userStr) : null;
}

// Logout handler
function logout() {
  localStorage.removeItem('auth_token');
  localStorage.removeItem('auth_user');
  window.location.href = '/login.html';
}

// API call with auth
async function apiCall(endpoint, method = 'GET', body = null) {
  const token = getAuthToken();
  if (!token) {
    logout();
    return null;
  }

  const options = {
    method,
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json',
    },
  };

  if (body) {
    options.body = JSON.stringify(body);
  }

  try {
    const response = await fetch(`${API_BASE}${endpoint}`, options);
    
    if (response.status === 401) {
      logout();
      return null;
    }

    return response;
  } catch (error) {
    console.error('API call failed:', error);
    throw error;
  }
}

const statusPill = document.getElementById("netStatus");
const syncPill = document.getElementById("syncStatus");
const inspectionForm = document.getElementById("inspectionForm");
const draftList = document.getElementById("draftList");
const formMessage = document.getElementById("formMessage");

const sectionTemplate = document.getElementById("sectionTemplate");
const fieldTemplate = document.getElementById("fieldTemplate");
const checkTemplate = document.getElementById("checkTemplate");

const REQUIRED_FIELDS = new Set([
  "landing_site_name",
  "owner_operator",
  "inspector",
  "operational_clearance",
  "d_value",
  "t_value",
  "date_current_inspection",
  "date_next_inspection"
]);

const formSchema = [
  {
    id: "cover",
    title: "Cover Details",
    fields: [
      { id: "landing_site_name", label: "Landing site name", type: "text", required: true },
      { id: "owner_operator", label: "Owner / Operator", type: "text", required: true },
      { id: "inspector", label: "Inspector(s)", type: "text", required: true },
      { id: "operational_clearance", label: "Operational Clearance (Day/Night)", type: "text", required: true },
      { id: "d_value", label: "D Value", type: "text", required: true },
      { id: "t_value", label: "T Value", type: "text", required: true },
      { id: "date_previous_inspection", label: "Date of Previous Inspection", type: "date" },
      { id: "date_current_inspection", label: "Date of Current Inspection", type: "date", required: true },
      { id: "date_next_inspection", label: "Date of Next Inspection", type: "date", required: true }
    ]
  },
  {
    id: "installation_details",
    title: "Installation Details",
    fields: [
      { id: "installation_type", label: "Installation Type", type: "text" },
      { id: "platform_type", label: "Platform / FPSO / Semi-Sub / Seismic / NUI / MOPU / Lay Barge / Crane Barge / Support Vessel / Jack up / Other", type: "text" },
      { id: "location_of_inspection", label: "Location of Inspection", type: "text" },
      { id: "area_of_operations", label: "Area of operations", type: "text" },
      { id: "owners_name", label: "Owners Name", type: "text" },
      { id: "owners_address", label: "Owners Address", type: "textarea" },
      { id: "contact_name", label: "Name of contact", type: "text" },
      { id: "job_position", label: "Job Position", type: "text" },
      { id: "phone", label: "Phone", type: "text" },
      { id: "email", label: "E-mail", type: "text" },
      { id: "helideck_drawings", label: "Helideck drawings details list", type: "textarea" },
      { id: "drawing_number", label: "Drawing number", type: "text" },
      { id: "drawing_date", label: "Date", type: "date" },
      { id: "plan_accuracy", label: "Do the plans accurately show the detail required? If no, state omissions. Ref: UKOOA Issue 5, Feb 2005", type: "textarea" },
      { id: "helideck_height_amsl", label: "Helideck Height AMSL (feet)", type: "text" },
      { id: "installation_identification", label: "Installation / Vessel identification", type: "text" },
      { id: "location_of_name", label: "Location of name (on all sides)", type: "text" },
      { id: "name_visibility", label: "Clearly visible", type: "text" },
      { id: "illumination", label: "Illumination", type: "text" },
      { id: "cap_437_ref", label: "CAP 437 FEB 2023 / UKOOA Issue 5, Feb 2005 (notes)", type: "text" }
    ]
  },
  {
    id: "manuals",
    title: "Manuals",
    checklist: [
      "UK CAA CAP 437, Edition 9",
      "Helicopter Procedures Manual detailing: Normal duties",
      "Helicopter Procedures Manual detailing: Emergency response",
      "Helicopter Procedures Manual detailing: Training",
      "Helicopter Procedures Manual detailing: Refuelling",
      "Helicopter Procedures Manual detailing: Dangerous Goods",
      "ICAO/IATA DG Regulations",
      "Passenger Safety Briefing for aircraft type being used",
      "Controlled Copy?",
      "In Date?",
      "Readily Available?",
      "Does the emergency procedures guide include aviation emergency procedures for HLO, OIM/Captain and Radio Operator?"
    ]
  },
  {
    id: "helideck_surface",
    title: "Helideck Surface",
    fields: [
      { id: "surface_colour", label: "Colour (If light grey or aluminium markings should be highlighted)", type: "text" },
      { id: "surface_condition", label: "Condition (Good, fair, or poor)", type: "text" },
      { id: "surface_cleanliness", label: "Cleanliness (Good, fair, or poor)", type: "text" },
      { id: "surface_level", label: "Level", type: "text" },
      { id: "surface_nonslip", label: "Nonslip characteristics (High/Medium/Low/Nil)", type: "text" }
    ]
  },
  {
    id: "friction_test",
    title: "Friction Test",
    fields: [
      { id: "last_friction_test", label: "Last friction test", type: "text" },
      { id: "friction_test_date", label: "Date", type: "date" },
      { id: "friction_test_expiry", label: "Expires", type: "date" },
      { id: "friction_exceptions", label: "For Exceptions: State", type: "textarea" },
      { id: "friction_tested_by", label: "Tested by whom", type: "text" },
      { id: "friction_value", label: "If friction value less than or equals 0.5mu", type: "text" },
      { id: "gutter_or_curb", label: "Gutter or raised curb around entire perimeter", type: "text" },
      { id: "drainage", label: "Drainage (Guttering clean?)", type: "text" },
      { id: "deck_sealed", label: "Deck sealed (no holes in deck for leaking/burning fuel)", type: "text" }
    ]
  },
  {
    id: "dimensions_markings",
    title: "Helideck Dimensions and Markings",
    fields: [
      { id: "overall_helideck_notes", label: "Overall Helideck (draw or paste design, secure digital copy, state dimensions)", type: "textarea" },
      { id: "overall_dimensions", label: "Overall Helideck Dimensions (include run-off if applicable)", type: "text" },
      { id: "rig_name_on_deck", label: "Installation/rig/vessel name painted on deck and position", type: "text" },
      { id: "name_min_height", label: "Minimum 1.2 metres high, white, not covered by landing net", type: "text" },
      { id: "perimeter_line", label: "Perimeter line (white, 30cm wide)", type: "text" },
      { id: "tdpm_circle", label: "Touchdown/Position Marking Circle (yellow, 1m, inner diameter 0.5D)", type: "text" },
      { id: "chevron", label: "Chevron (0.79m x 0.10m), colour, angle, location", type: "text" },
      { id: "d_value_marking", label: "D value marking (correct value, colour, height, location)", type: "text" },
      { id: "h_marking", label: "H marking (size, colour, location, swung)", type: "text" },
      { id: "max_mass", label: "Max Allowable Mass (correct value, size, colour, location)", type: "text" },
      { id: "integrity_report_date", label: "Helideck integrity report/document date", type: "date" }
    ]
  },
  {
    id: "authorized_aircraft",
    title: "Authorized Aircraft",
    fields: [
      { id: "authorized_aircraft_list", label: "Authorized aircraft types (list or select)", type: "textarea" }
    ]
  },
  {
    id: "obstruction_environment",
    title: "Obstruction Environment",
    fields: [
      { id: "sector_210", label: "210 degree sector obstruction(s): location and height above helideck level", type: "textarea" },
      { id: "sector_150", label: "150 degree sector obstruction(s): location and height above helideck level", type: "textarea" },
      { id: "sector_180", label: "180 degree sector 5:1 falling gradient obstruction(s): what and where", type: "textarea" },
      { id: "combined_operation", label: "Is combined operation being conducted? Duration. OFS compromised?", type: "textarea" },
      { id: "obstacle_penetration", label: "Obstacle penetration of 5:1 falling gradient", type: "text" },
      { id: "prohibition_marker", label: "Yellow prohibition marker placed on decks deemed unavailable", type: "text" },
      { id: "operation_limitations", label: "Any limitations on operation", type: "textarea" },
      { id: "lssr_update", label: "LSSR update (if applicable)", type: "text" }
    ]
  },
  {
    id: "helideck_net",
    title: "Helideck Net (If Applicable)",
    checklist: [
      "Observation",
      "Net requirements",
      "Age/Condition",
      "Tension (lift not above 25cm near centre)",
      "Location (covers aiming circle and not rig identification and max mass)",
      "Material (sisal, not polypropylene)",
      "Type (threaded/knotted/inter-twined)",
      "Tie-down points every 1.5 metres",
      "Method of securing (rope/webbing/ratchet tensioners)",
      "Net size (9x9m, 12x12m, 15x15m)",
      "Mesh size (200mm/250mm)",
      "Rope diameter (200mm)"
    ]
  },
  {
    id: "perimeter_safety_net",
    title: "Perimeter Safety Net",
    checklist: [
      "Material and condition (wire mesh or braided nylon preferred)",
      "Width from deck edge (1.5m)",
      "Hammock effect",
      "Slope (10 degrees), highest point above deck level",
      "Inspection and test schedule (weekly offshore, annual onshore test)"
    ]
  },
  {
    id: "perimeter_lighting",
    title: "Perimeter Lighting",
    checklist: [
      "Colour and serviceability (green lights coincident with perimeter line)",
      "Height above deck level (<25cm) and distance apart (3m)",
      "Position (delineate safe landing area only, visible 360 degrees)",
      "Power rating (30 candelas green)"
    ]
  },
  {
    id: "perimeter_lighting_additional",
    title: "Perimeter Lighting - Additional",
    checklist: [
      "Limit of safe landing area (red lights for unsafe sectors)",
      "Emergency power supply (vessel, perimeter, flood lights)",
      "Serviceability",
      "Switching controlled from radio room or by HLO"
    ]
  },
  {
    id: "helideck_flood_lighting",
    title: "Helideck Flood Lighting",
    fields: [
      { id: "deck_lighting_system", label: "In-deck lighting system or deck mounted", type: "text" },
      { id: "illuminates_helideck", label: "Illuminates the helideck", type: "text" },
      { id: "flood_number_type", label: "Number and type", type: "text" },
      { id: "flood_dazzle", label: "Dazzle protection and power rating", type: "textarea" },
      { id: "flood_spectral", label: "Spectral distribution provides adequate illumination", type: "text" }
    ]
  },
  {
    id: "general_lighting",
    title: "General Lighting",
    checklist: [
      "Installation/rig/vessel floodlighting angled so as not to dazzle pilots",
      "Structures over 15m above deck level: red lights every 10m",
      "Highest point: omni-directional light intensity 50 to 200 candela"
    ]
  },
  {
    id: "obstruction_marking",
    title: "Obstruction Marking and Lighting",
    checklist: [
      "Obstruction lighting (flare stacks and booms may be floodlit)",
      "Colour of cranes and obstacles (black/yellow or red/white)"
    ]
  },
  {
    id: "status_light",
    title: "Status Light",
    checklist: [
      "Flashing red light signals not safe to land",
      "Automatic switching",
      "Visible from all directions",
      "Connected to emergency power supply",
      "Dual filament"
    ]
  },
  {
    id: "additional_equipment",
    title: "Additional Equipment",
    checklist: [
      "Chocks (minimum 6, rubber, short connecting rope)",
      "Tie-down strops (minimum 6, 12,000 lbs)",
      "Prohibited landing marker (4x4m red flag with diagonal yellow cross)",
      "Breathing apparatus (min 2 sets with reserve cylinders)",
      "Windsock (number, position, lit for night operations)",
      "Helicopter starter unit (rectifier/battery trolley, 28V DC)"
    ]
  },
  {
    id: "turbulence",
    title: "Turbulence",
    checklist: [
      "Structures likely to cause turbulence (jackup legs, derricks, cranes)",
      "Hot emissions likely to cause turbulence (flares, turbine exhausts)",
      "Cold emissions (vents, blow down)",
      "Air gap beneath helideck"
    ]
  },
  {
    id: "access_points",
    title: "Access Points",
    checklist: [
      "Locations (minimum 2, upwind and downwind)",
      "Handrails (collapsible/fixed/conspicuous)",
      "Steps clean and no trip/slip hazard",
      "Safety notices available and readable",
      "Control of non-helideck crew and passengers"
    ]
  },
  {
    id: "fire_fighting",
    title: "Fire Fighting Equipment",
    checklist: [
      "Foam monitors quantity and type (minimum 2)",
      "Fire main pressure always on during helicopter operations",
      "Foam concentrate type and quantity",
      "Back-up concentrate 100 percent",
      "Foam test certificate date done/due",
      "Foam hand branches quantity, type, condition",
      "Hydrant points quantity and location",
      "Foam system maintenance and testing"
    ]
  },
  {
    id: "portable_extinguishers",
    title: "Portable Extinguishers and Back-up Media",
    checklist: [
      "Portable extinguishers (dry powder and CO2) minimum numbers",
      "Back-up stocks of complementary media (100 percent)"
    ]
  },
  {
    id: "rescue_equipment",
    title: "Rescue Equipment",
    checklist: [
      "Crash box (number, accessible, weather-proof)",
      "Adjustable wrench",
      "Large rescue axe",
      "Bolt cutters",
      "Large crowbar",
      "Heavy duty hacksaw",
      "Heavy duty blades",
      "Fire resistant blanket",
      "Side-cutting pliers",
      "Assorted screwdrivers",
      "Harness knife",
      "MMMF filter masks",
      "Ladder (two section aluminium)",
      "Hook, grab or salving",
      "Fire resistant gloves",
      "Lifeline (5cm, 15m)",
      "Power cutting tool"
    ]
  },
  {
    id: "water_rescue",
    title: "Water Rescue",
    checklist: [
      "Water rescue boat available during air operations",
      "Trained rescue personnel manning rescue boat",
      "Procedure in place for water rescue"
    ]
  },
  {
    id: "protective_clothing",
    title: "Protective Clothing",
    checklist: [
      "Protective clothing for each helideck crew member",
      "HLO identifying waistcoat",
      "Stowage for fire fighting equipment"
    ]
  },
  {
    id: "radio_operator",
    title: "Radio Operator and Radio Equipment",
    checklist: [
      "Radio operator competent and licensed",
      "Daily reports and departure messages contain necessary information",
      "DP vessels aircrew alert procedures",
      "Portable multi-channel hand-held radio (air band)",
      "Airband radios (type, condition, approval)",
      "Portable VHF FM",
      "NDB (type, frequency, call sign, approval)",
      "Radio log for helicopter movements"
    ]
  },
  {
    id: "met_reports",
    title: "Meteorological Reports and Equipment",
    checklist: [
      "Weather reports in standard ICAO format",
      "Anemometer fixed calibration date",
      "Anemometer hand calibration date",
      "Wind direction calibration date",
      "Air temperature sensor in representative location",
      "Precision barometer/twin altimeters calibration date",
      "Visibility and cloud base equipment calibration date",
      "Pitch, roll and heave equipment calibration date",
      "Maintenance schedule for instruments"
    ]
  },
  {
    id: "miscellaneous",
    title: "Miscellaneous",
    checklist: [
      "Pre-landing checks in accordance with checklist and SOPs",
      "Dangerous goods reference material and documentation on board",
      "Helicopter emergency diagrams and safety posters location",
      "Helideck training exercises record (monthly)",
      "Video/CD player for passenger safety briefings",
      "Heavy-duty scales for baggage and freight calibration",
      "Control of access to screened baggage/cargo",
      "Documented baggage processing procedures",
      "Stretcher type and location"
    ]
  },
  {
    id: "aviation_fuel",
    title: "Aviation Fuel System",
    fields: [
      { id: "fuel_system_type", label: "Type of system", type: "text" },
      { id: "fuel_system_make", label: "Make", type: "text" },
      { id: "fuel_system_condition", label: "Condition", type: "text" },
      { id: "fuel_pump_light", label: "Pump running light", type: "text" },
      { id: "fuel_quick_release", label: "Quick release connector on bonding lead", type: "text" },
      { id: "fuel_last_inspection", label: "Date of last inspection", type: "date" },
      { id: "fuel_authorised_inspector", label: "Authorised fuel inspector", type: "text" },
      { id: "fuel_authorised_date", label: "Date", type: "date" },
      { id: "fuel_bhnl_inspector", label: "BHNL fuel inspector", type: "text" },
      { id: "fuel_bhnl_date", label: "BHNL date", type: "date" },
      { id: "fuel_procedures", label: "On-site procedures (weekly/daily checks, Shell test kits)", type: "textarea" },
      { id: "fuel_documentation", label: "Documentation (retention of paperwork)", type: "textarea" }
    ]
  },
  {
    id: "helideck_crew",
    title: "Helideck Crew",
    checklist: [
      "HLO training to OPITO or equivalent (HLO course validity, heli team members, radio licence)",
      "HDA training to OPITO or equivalent (heli team member validity, DG by air awareness)",
      "Heli admin (radio licence, DG by air awareness)",
      "Manning policy (HLO + 3, etc)",
      "Documentation and continuation training (exercises and drills)"
    ]
  },
  {
    id: "dangerous_goods",
    title: "Dangerous Goods",
    checklist: [
      "Qualified personnel",
      "Packaging"
    ]
  },
  {
    id: "risk_assessment",
    title: "Risk Assessment for Sub-1D Helidecks",
    checklist: [
      "Obstacle clearance for TLOF and LOS",
      "Visual cues available",
      "Space for passengers",
      "Helicopter tie-down",
      "Touchdown/positioning inaccuracies reduction",
      "Reduction in helpful ground cushion effect from rotor downwash"
    ]
  },
  {
    id: "executive_summary",
    title: "Executive Summary",
    fields: [
      { id: "executive_summary", label: "Inspectors Executive Summary", type: "textarea" }
    ]
  },
  {
    id: "non_conformances",
    title: "Formal List of Non-Compliance",
    table: {
      id: "non_conformances",
      columns: [
        "Non-Compliance",
        "Action Party",
        "Priority (High/Med/Low)",
        "Target Closure Date",
        "Date Closed",
        "How was Closure Verified"
      ]
    }
  },
  {
    id: "recommendations",
    title: "Recommendations (Not Mandatory)",
    table: {
      id: "recommendations",
      columns: ["Recommendation", "Action Party", "Priority", "Date Closed", "Comments"]
    }
  },
  {
    id: "hll",
    title: "Helideck Limitations List (HLL)",
    table: {
      id: "hll",
      columns: [
        "Aircraft Type",
        "Limitation Imposed",
        "Reason for Limitation",
        "Date Limitation Effective",
        "Date Limitation Removed",
        "Reason Limitation Removed"
      ]
    }
  },
  {
    id: "endorsement",
    title: "Landing Site Inspection Endorsement",
    fields: [
      { id: "endorsement_installation", label: "Installation/Vessel Name", type: "text" },
      { id: "endorsement_base", label: "Base", type: "text" },
      { id: "endorsement_date", label: "Inspection Date", type: "date" },
      { id: "inspector_name", label: "Helideck Inspector Name", type: "text" },
      { id: "inspector_signature_typed", label: "Helideck Inspector Signature (typed)", type: "text" },
      { id: "inspector_signature_date", label: "Inspector Signature Date", type: "date" },
      { id: "managing_pilot_name", label: "Managing Pilot / Chief Pilot Name", type: "text" },
      { id: "managing_pilot_signature", label: "Managing Pilot / Chief Pilot Signature (typed)", type: "text" },
      { id: "managing_pilot_date", label: "Managing Pilot / Chief Pilot Signature Date", type: "date" },
      { id: "head_ops_name", label: "Head of Flight Operations Name", type: "text" },
      { id: "head_ops_signature", label: "Head of Flight Operations Signature (typed)", type: "text" },
      { id: "head_ops_date", label: "Head of Flight Operations Signature Date", type: "date" },
      { id: "quality_manager_name", label: "Quality and Safety Manager Name", type: "text" },
      { id: "quality_manager_signature", label: "Quality and Safety Manager Signature (typed)", type: "text" },
      { id: "quality_manager_date", label: "Quality and Safety Manager Signature Date", type: "date" },
      { id: "hump_entered_date", label: "Helideck Inspection Report entered on HUMP Date", type: "date" }
    ]
  },
  {
    id: "signatures",
    title: "Drawn Signatures (Optional)",
    fields: [
      { id: "signature_inspector_drawn", label: "Helideck Inspector Signature (drawn)", type: "signature" },
      { id: "signature_managing_pilot_drawn", label: "Managing Pilot / Chief Pilot Signature (drawn)", type: "signature" },
      { id: "signature_head_ops_drawn", label: "Head of Flight Operations Signature (drawn)", type: "signature" },
      { id: "signature_quality_manager_drawn", label: "Quality and Safety Manager Signature (drawn)", type: "signature" }
    ]
  }
];

let currentReportId = null;

function setNetworkStatus() {
  const online = navigator.onLine;
  statusPill.textContent = online ? "Online" : "Offline";
  statusPill.style.background = online ? "rgba(46, 204, 113, 0.25)" : "rgba(231, 76, 60, 0.25)";
}

function updateSyncStatus(text) {
  syncPill.textContent = text;
}

function createField(field, value) {
  const node = fieldTemplate.content.cloneNode(true);
  const wrapper = node.querySelector(".field");
  const label = node.querySelector(".field-label");
  const inputWrap = node.querySelector(".field-input");

  label.textContent = field.label + (field.required ? " *" : "");

  let input;
  if (field.type === "signature") {
    const pad = document.createElement("div");
    pad.className = "signature-pad";

    const canvas = document.createElement("canvas");
    canvas.width = 800;
    canvas.height = 240;
    canvas.dataset.signatureFor = field.id;

    const hidden = document.createElement("input");
    hidden.type = "hidden";
    hidden.name = field.id;
    hidden.value = value || "";

    const clearBtn = document.createElement("button");
    clearBtn.type = "button";
    clearBtn.className = "secondary";
    clearBtn.textContent = "Clear Signature";
    clearBtn.addEventListener("click", () => {
      const ctx = canvas.getContext("2d");
      ctx.clearRect(0, 0, canvas.width, canvas.height);
      hidden.value = "";
    });

    pad.appendChild(canvas);
    pad.appendChild(clearBtn);
    pad.appendChild(hidden);
    inputWrap.appendChild(pad);
    return wrapper;
  }

  if (field.type === "textarea") {
    input = document.createElement("textarea");
  } else if (field.type === "select") {
    input = document.createElement("select");
    (field.options || []).forEach((opt) => {
      const option = document.createElement("option");
      option.value = opt;
      option.textContent = opt;
      input.appendChild(option);
    });
  } else {
    input = document.createElement("input");
    input.type = field.type || "text";
  }

  input.name = field.id;
  if (field.required) input.required = true;
  input.value = value || "";
  inputWrap.appendChild(input);

  return wrapper;
}

function createChecklistItem(sectionId, label, value = {}) {
  const node = checkTemplate.content.cloneNode(true);
  const labelEl = node.querySelector(".check-label");
  const controls = node.querySelector(".check-controls");

  labelEl.textContent = label;

  const radioGroup = document.createElement("div");
  radioGroup.className = "radio-group";
  ["ok", "not_ok", "na"].forEach((opt) => {
    const radioLabel = document.createElement("label");
    const radio = document.createElement("input");
    radio.type = "radio";
    radio.name = `${sectionId}__${slug(label)}__status`;
    radio.value = opt;
    if (value.status === opt) radio.checked = true;
    radioLabel.appendChild(radio);
    radioLabel.appendChild(document.createTextNode(opt.replace("_", " ")));
    radioGroup.appendChild(radioLabel);
  });

  const comment = document.createElement("textarea");
  comment.name = `${sectionId}__${slug(label)}__comment`;
  comment.placeholder = "Comment";
  comment.value = value.comment || "";

  controls.appendChild(radioGroup);
  controls.appendChild(comment);

  return node;
}

function createTable(sectionId, table, value = []) {
  const wrapper = document.createElement("div");
  wrapper.className = "table-controls";

  const tableEl = document.createElement("table");
  tableEl.className = "dynamic-table";
  const thead = document.createElement("thead");
  const headRow = document.createElement("tr");
  table.columns.forEach((col) => {
    const th = document.createElement("th");
    th.textContent = col;
    headRow.appendChild(th);
  });
  thead.appendChild(headRow);
  tableEl.appendChild(thead);

  const tbody = document.createElement("tbody");
  tableEl.appendChild(tbody);

  function addRow(rowData = []) {
    const tr = document.createElement("tr");
    table.columns.forEach((col, idx) => {
      const td = document.createElement("td");
      const input = document.createElement("input");
      input.type = "text";
      input.name = `${sectionId}__${table.id}__${tbody.children.length}__${idx}`;
      input.value = rowData[idx] || "";
      td.appendChild(input);
      tr.appendChild(td);
    });
    tbody.appendChild(tr);
  }

  if (value.length) {
    value.forEach((row) => addRow(row));
  } else {
    addRow();
  }

  const addBtn = document.createElement("button");
  addBtn.type = "button";
  addBtn.className = "secondary";
  addBtn.textContent = "Add Row";
  addBtn.addEventListener("click", () => addRow());

  wrapper.appendChild(tableEl);
  wrapper.appendChild(addBtn);

  return wrapper;
}

function renderForm(data = {}) {
  inspectionForm.innerHTML = "";

  formSchema.forEach((section) => {
    const sectionNode = sectionTemplate.content.cloneNode(true);
    sectionNode.querySelector(".section-title").textContent = section.title;
    const body = sectionNode.querySelector(".section-body");

    if (section.fields) {
      section.fields.forEach((field) => {
        const fieldValue = data[field.id];
        body.appendChild(createField(field, fieldValue));
      });
    }

    if (section.checklist) {
      const listValue = data[section.id] || {};
      section.checklist.forEach((item) => {
        body.appendChild(createChecklistItem(section.id, item, listValue[item] || {}));
      });
    }

    if (section.table) {
      const tableValue = data[section.table.id] || [];
      body.appendChild(createTable(section.id, section.table, tableValue));
    }

    inspectionForm.appendChild(sectionNode);
  });

  setupSignaturePads();
}

function slug(text) {
  return text.toLowerCase().replace(/[^a-z0-9]+/g, "_").replace(/_+/g, "_");
}

function collectFormData() {
  const data = {
    form_version: FORM_VERSION,
    updated_at: new Date().toISOString()
  };

  formSchema.forEach((section) => {
    if (section.fields) {
      section.fields.forEach((field) => {
        const input = inspectionForm.querySelector(`[name="${field.id}"]`);
        if (input) data[field.id] = input.value || "";
      });
    }

    if (section.checklist) {
      const sectionData = {};
      section.checklist.forEach((item) => {
        const status = inspectionForm.querySelector(`[name="${section.id}__${slug(item)}__status"]:checked`);
        const comment = inspectionForm.querySelector(`[name="${section.id}__${slug(item)}__comment"]`);
        sectionData[item] = {
          status: status ? status.value : "",
          comment: comment ? comment.value : ""
        };
      });
      data[section.id] = sectionData;
    }

    if (section.table) {
      const rows = [];
      const prefix = `${section.id}__${section.table.id}__`;
      const inputs = inspectionForm.querySelectorAll(`[name^="${prefix}"]`);
      const rowsMap = {};
      inputs.forEach((input) => {
        const parts = input.name.replace(prefix, "").split("__");
        const rowIndex = Number(parts[0]);
        const colIndex = Number(parts[1]);
        if (!rowsMap[rowIndex]) rowsMap[rowIndex] = [];
        rowsMap[rowIndex][colIndex] = input.value || "";
      });
      Object.keys(rowsMap).forEach((key) => rows.push(rowsMap[key]));
      data[section.table.id] = rows;
    }
  });

  return data;
}

function validateRequired(data) {
  const missing = [];
  REQUIRED_FIELDS.forEach((field) => {
    if (!data[field]) missing.push(field);
  });
  return missing;
}

async function loadDrafts() {
  const inspections = await idb.dbAll("inspections");
  draftList.innerHTML = "";

  if (!inspections.length) {
    draftList.innerHTML = "<div class=\"muted\">No drafts yet. Start a new inspection.</div>";
    return;
  }

  inspections
    .sort((a, b) => new Date(b.updated_at) - new Date(a.updated_at))
    .forEach((inspection) => {
      const landingSite = inspection.form_data?.cover?.landing_site_name || "Untitled Inspection";
      const statusBadgeColor = inspection.status === "submitted" ? "accent" : "muted";
      
      const item = document.createElement("div");
      item.className = "list-item";
      item.innerHTML = `
        <strong>${landingSite}</strong>
        <div class="muted">
          Status: <span style="color: var(--${statusBadgeColor})">${inspection.status}</span> | 
          Updated: ${new Date(inspection.updated_at).toLocaleString()}
          ${inspection.synced ? " ✓ Synced" : " ○ Not synced"}
        </div>
      `;

      const actions = document.createElement("div");
      actions.className = "actions";

      const openBtn = document.createElement("button");
      openBtn.type = "button";
      openBtn.className = "secondary";
      openBtn.textContent = "Edit";
      openBtn.addEventListener("click", () => loadReport(inspection.uuid));

      const deleteBtn = document.createElement("button");
      deleteBtn.type = "button";
      deleteBtn.className = "secondary";
      deleteBtn.textContent = "Delete";
      deleteBtn.addEventListener("click", async () => {
        if (confirm("Delete this inspection?")) {
          await idb.dbDelete("inspections", inspection.uuid);
          await idb.dbDelete("queue", inspection.uuid);
          await loadDrafts();
        }
      });

      actions.appendChild(openBtn);
      actions.appendChild(deleteBtn);
      item.appendChild(actions);
      draftList.appendChild(item);
    });
}

async function loadReport(uuid) {
  const inspection = await idb.dbGet("inspections", uuid);
  if (!inspection) return;
  currentReportId = uuid;
  renderForm(inspection.form_data || {});
}

async function saveDraft(syncStatus = "draft") {
  const data = collectFormData();
  const missing = validateRequired(data);
  if (missing.length && syncStatus === "submitted") {
    formMessage.textContent = `Missing required fields: ${missing.join(", ")}`;
    formMessage.style.color = "var(--danger)";
    return false;
  }

  // Use UUID for consistency with backend
  const uuid = currentReportId || crypto.randomUUID();
  const now = new Date().toISOString();

  const inspection = {
    uuid,
    form_data: data,
    status: syncStatus === "submitted" ? "submitted" : "draft",
    created_at: now,
    updated_at: now,
  };

  // Store in IndexedDB with full structure
  await idb.dbPut("inspections", {
    uuid,
    form_data: data,
    status: inspection.status,
    created_at: now,
    updated_at: now,
    synced: false,
  });

  currentReportId = uuid;

  // Queue for sync if submitted
  if (syncStatus === "submitted") {
    await idb.dbPut("queue", {
      uuid,
      inspection,
      synced: false,
    });
  }

  await loadDrafts();
  formMessage.textContent = syncStatus === "submitted" ? "Submitted! Will sync when online." : "Draft saved locally.";
  formMessage.style.color = "var(--muted)";
  return true;
}

async function syncQueue() {
  if (!navigator.onLine) {
    updateSyncStatus("Offline");
    return;
  }

  updateSyncStatus("Syncing...");

  try {
    // Get pending inspections from queue
    const queueItems = await idb.dbGetAll("queue");
    if (!queueItems.length) {
      updateSyncStatus("Idle");
      return;
    }

    // Prepare payload
    const payload = queueItems.map((item) => item.inspection);

    // Call backend sync endpoint
    const response = await apiCall("/inspections/sync", "POST", payload);

    if (!response || !response.ok) {
      throw new Error("Sync failed");
    }

    const results = await response.json();

    // Process results
    for (const result of results) {
      if (result.status === "synced" || result.status === "skipped") {
        // Mark as synced in local db
        const inspection = await idb.dbGet("inspections", result.uuid);
        if (inspection) {
          inspection.synced = true;
          await idb.dbPut("inspections", inspection);
        }

        // Remove from queue
        await idb.dbDelete("queue", result.uuid);
      }
    }

    await loadDrafts();
    updateSyncStatus("Synced");

    formMessage.textContent = "All submissions synced successfully!";
    formMessage.style.color = "var(--ok)";

    setTimeout(() => {
      formMessage.textContent = "";
    }, 3000);
  } catch (error) {
    console.error("Sync error:", error);
    updateSyncStatus("Sync failed");
    formMessage.textContent = "Sync failed. Will retry when online.";
    formMessage.style.color = "var(--danger)";
  }
}

function setupSignaturePads() {
  document.querySelectorAll(".signature-pad canvas").forEach((canvas) => {
    const ctx = canvas.getContext("2d");
    let drawing = false;
    const hidden = canvas.parentElement.querySelector(`input[name=\"${canvas.dataset.signatureFor}\"]`);

    const start = (e) => {
      drawing = true;
      ctx.beginPath();
      const { x, y } = getPoint(e, canvas);
      ctx.moveTo(x, y);
    };

    const move = (e) => {
      if (!drawing) return;
      const { x, y } = getPoint(e, canvas);
      ctx.lineTo(x, y);
      ctx.stroke();
    };

    const end = () => {
      drawing = false;
      if (hidden) hidden.value = canvas.toDataURL("image/png");
    };

    canvas.addEventListener("mousedown", start);
    canvas.addEventListener("mousemove", move);
    window.addEventListener("mouseup", end);

    canvas.addEventListener("touchstart", (e) => {
      e.preventDefault();
      start(e.touches[0]);
    });
    canvas.addEventListener("touchmove", (e) => {
      e.preventDefault();
      move(e.touches[0]);
    });
    canvas.addEventListener("touchend", end);
  });
}

function getPoint(e, canvas) {
  const rect = canvas.getBoundingClientRect();
  return {
    x: e.clientX - rect.left,
    y: e.clientY - rect.top
  };
}

async function init() {
  // Check authentication
  if (!checkAuth()) {
    return;
  }

  // Show logged-in user info
  const user = getCurrentUser();
  if (user) {
    // You can add a user display element here if needed
    console.log(`Logged in as: ${user.name}`);
  }

  setNetworkStatus();
  window.addEventListener("online", () => {
    setNetworkStatus();
    syncQueue();
  });
  window.addEventListener("offline", setNetworkStatus);

  renderForm();
  await loadDrafts();

  if ("serviceWorker" in navigator) {
    navigator.serviceWorker.register("sw.js");
  }

  // Event listeners
  document.getElementById("newReportBtn").addEventListener("click", () => {
    currentReportId = null;
    renderForm();
  });
  document.getElementById("saveDraftBtn").addEventListener("click", () => saveDraft("draft"));
  document.getElementById("submitBtn").addEventListener("click", async () => {
    const ok = await saveDraft("submitted");
    if (ok) syncQueue();
  });
  document.getElementById("syncNowBtn").addEventListener("click", syncQueue);
  document.getElementById("logoutBtn").addEventListener("click", logout);
}

init();
