

/* =======================
   SLIDER LOGIC (FINAL FIX)
======================= */

document.addEventListener("DOMContentLoaded", function () {

    const heroSection = document.querySelector("#hero, #service-hero");
    
    if (heroSection) {
        const slides = heroSection.querySelectorAll(".hero-slider img");
        const dots = heroSection.querySelectorAll(".slick-dots .dot");
        const nextBtn = heroSection.querySelector(".slick-next");
        const prevBtn = heroSection.querySelector(".slick-prev");

        let currentSlide = 0;
        let slideInterval;

        function showSlide(index) {
            if (index >= slides.length) currentSlide = 0;
            else if (index < 0) currentSlide = slides.length - 1;
            else currentSlide = index;

            slides.forEach((slide, i) => {
                slide.classList.toggle("active", i === currentSlide);
            });

            if (dots.length) {
                dots.forEach((dot, i) => {
                    dot.classList.toggle("active", i === currentSlide);
                });
            }
        }

        function nextSlide() {
            showSlide(currentSlide + 1);
        }

        function prevSlide() {
            showSlide(currentSlide - 1);
        }

        function startSlider() {
            slideInterval = setInterval(nextSlide, 2000);
        }

        function stopSlider() {
            clearInterval(slideInterval);
        }

        // Buttons
        if (nextBtn) {
            nextBtn.addEventListener("click", () => {
                nextSlide();
                stopSlider();
                startSlider();
            });
        }

        if (prevBtn) {
            prevBtn.addEventListener("click", () => {
                prevSlide();
                stopSlider();
                startSlider();
            });
        }

        // Dots
        if (dots.length) {
            dots.forEach((dot, i) => {
                dot.addEventListener("click", () => {
                    showSlide(i);
                    stopSlider();
                    startSlider();
                });
            });
        }

        // Init slider
        showSlide(0);
        startSlider();
    }

});



function openModal(course) {
  const modal = document.getElementById("courseModal");
  const title = document.getElementById("modalTitle");
  const list = document.getElementById("modalList");

  const data = {
    cpp: {
      title: "C C++ Programming",
      items: [
         "Introduction to Programming & Problem Solving",
    "C Language Fundamentals (Variables, Data Types, Operators)",
    "Control Statements (If-Else, Switch, Loops)",
    "Functions & Recursion",
    "Arrays, Strings & Pointers",
    "Structures & File Handling",

    "Object-Oriented Programming (C++)",
    "Classes, Objects & Constructors",
    "Inheritance, Polymorphism & Encapsulation",

    "Data Structures Basics",
    "Stack & Queue Implementation",
    "Linked List (Singly, Doubly, Circular)",
    "Searching Techniques (Linear, Binary)",
    "Sorting Algorithms (Bubble, Selection, Merge)",
    "Introduction to Trees & Graphs",

    "Hands-on Coding Practice",
    "Mini Projects & Interview Preparation"
  ]
    },

    php: {
      title: "PHP Development",
      items: [
         "Introduction to Web Development & PHP",
    "Setting up Local Server (XAMPP/WAMP)",
    "PHP Syntax, Variables & Data Types",
    "Operators & Conditional Statements",
    "Loops (For, While, Foreach)",

    "Working with Forms (GET & POST)",
    "Form Validation & User Input Handling",
    "Sessions & Cookies Management",

    "MySQL Database Basics",
    "Database Connection with PHP",
    "CRUD Operations (Create, Read, Update, Delete)",

    "File Handling & File Upload",
    "Error Handling & Debugging",

    "Introduction to MVC Structure",
    "Basic Security Concepts (SQL Injection, XSS)",

    "Mini Project (Dynamic Website)",
    "Live Project & Deployment Basics"

      ]
    },

    web: {
      title: "Web Development",
      items: [
         "Introduction to Web Development",
    "HTML5 (Structure of Web Pages)",
    "CSS3 (Styling, Colors, Layouts)",
    "Responsive Design (Mobile Friendly UI)",

    "Flexbox & CSS Grid",
    "Bootstrap Framework",

    "JavaScript Basics",
    "DOM Manipulation",
    "Events & Functions",

    "Form Handling & Validation",
    "Basic UI/UX Principles",

    "Version Control (Git & GitHub)",
    "Website Hosting & Deployment",

    "Mini Projects (Landing Page, Forms)",
    "Live Project (Complete Website Development)"
      ]
    },

    java: {
      title: "Java Programming",
      items: [
        "Core Java Basics",
    "Data Types & Variables",
    "Operators & Control Statements",
    "OOP Concepts",
    "Classes & Objects",
    "Constructors & Methods",
    "Inheritance & Polymorphism",
    "Encapsulation & Abstraction",
    "Exception Handling",
    "Collections Framework (List, Set, Map)",
    "Generics & Iterators",
    "File Handling",
    "Java I/O Streams",
    "JDBC & Database Connectivity",
    "Multithreading & Concurrency",
    "Java 8 Features (Streams, Lambda Expressions)",
    "GUI Programming with Swing & JavaFX",
    "Networking in Java",
    "Design Patterns Basics",
    "Mini Project"
      ]
    },

    salesforce: {
      title: "Salesforce Training",
      items: [
         "Introduction to Salesforce",
    "Salesforce Architecture & Cloud Overview",
    "Data Modeling & Management",
    "Objects, Fields & Relationships",
    "Validation Rules & Workflow Rules",
    "Process Builder & Flow Builder",
    "Reports & Dashboards",
    "Security & Access Management (Profiles, Roles, Permission Sets)",
    "Salesforce Lightning Experience",
    "Apex Programming Basics",
    "Triggers & Classes",
    "Visualforce Pages",
    "Lightning Components & LWC",
    "SOQL & SOSL Queries",
    "Data Import & Export Tools",
    "AppExchange & Managed Packages",
    "Change Sets & Deployment",
    "Salesforce APIs & Integration Basics",
    "Testing & Debugging in Salesforce",
    "Mini Project / Real-time Use Case Implementation"
      ]
    },

   software: {
  title: "Software Development",
  items: [
     "Introduction to Software Development",
    "Software Development Life Cycle (SDLC)",
    "Requirement Analysis & Documentation",
    "Programming Fundamentals (Variables, Data Types, Loops)",
    "Object-Oriented Programming (OOP Concepts)",
    "Data Structures & Algorithms",
    "Version Control with Git & GitHub",
    "Database Fundamentals (SQL & NoSQL)",
    "Frontend Development Basics (HTML, CSS, JavaScript)",
    "Backend Development Basics (Node.js, Java, Python, or C#)",
    "API Development & Integration (REST & SOAP)",
    "Software Testing & QA Basics",
    "Debugging & Troubleshooting",
    "Agile Methodology & Scrum Framework",
    "Design Patterns & Best Practices",
    "Software Architecture Concepts",
    "CI/CD & DevOps Basics",
    "Cloud Basics (AWS, Azure, or Google Cloud)",
    "Security Fundamentals in Software Development",
    "Project Management Tools (JIRA, Trello)",
    "Mini Project / Real-world Application Development"
  ]
},  // ✅ FIXED





fly: {
  title: "Fly Management",
  items: [
    "Types of Flies: Flesh fly, Drain fly, Fruit fly, House fly (most common in Indian homes)",

    "Egg Laying: Female flies lay hundreds of eggs on decaying food, garbage, and waste",
    "Larvae Stage: Eggs hatch into maggots that feed and grow rapidly",
    "Pupae to Adult: Larvae transform into adult flies ready to reproduce",
    "Favorable Conditions: Warm, moist environments speed up infestation",

    "Attraction: Open food, sugary spills, and ripe fruits attract flies",
    "Waste Issues: Uncovered garbage and organic waste increase breeding",
    "Moist Areas: Drains, leakage, and stagnant water attract drain flies",
    "Decaying Matter: Rotting food and dead animals attract flesh flies",

    "Spray Treatment: Targeted spraying at breeding and resting areas",
    "Garden Treatment: Spraying around plants and moist soil",
    "Drain Treatment: Treatment of drains, manholes, and stagnant water",
    "Waste Area: Spraying near garbage and disposal zones",
    "Infested Zones: Focus on walls, corners, and dark areas",

    "Merits: Controlled use of fly-specific chemicals",
    "Coverage: Complete treatment of garden, drainage & waste areas",
    "Effective Technique: Ensures long-term control",

    "Service Option: One-time treatment for instant relief",
    "Annual Plan: 3 scheduled treatments for year-long protection"
  ]
}


,
cockroach: {
  title: "Cockroach & Crawling Pest Management",
  items: [
    "Common Pests: Cockroaches, ants, and silverfish found in kitchens, bathrooms, drains, basements, and storage areas",

    "Health Risks: Contaminate food and spread bacteria causing diarrhea, food poisoning, and allergies",

    "Cockroach Life Cycle: Egg cases (15–40 eggs) hidden in dark moist areas",
    "Nymph Stage: Small wingless insects feeding on crumbs and grease",
    "Growth: Multiple molting stages before becoming adults",
    "Favorable Conditions: Warmth, moisture, food, and hidden spaces",

    "Ant Behavior: Colonies led by a queen producing thousands of eggs",
    "Food Search: Worker ants follow scent trails to sugary food and crumbs",

    "Silverfish Growth: Eggs laid in cracks, feeding on paper, starch, and fabrics",
    "Environment: Prefer damp, dark places",

    "Infestation Areas: Kitchens, pantries, bathrooms, under sinks, behind appliances, electrical outlets",
    "Entry Points: Drains, vents, cracks, windows, and infested materials",

    "Inspection: Detailed site analysis to identify pests, hiding spots, and entry points",

    "Cockroach Treatment: Gel baiting, odorless sprays, dusting, fogging for severe cases",
    "Ant Control: Bait stations, sealing cracks, perimeter treatment",
    "Silverfish Control: Sprays in damp areas, dehumidification, storage improvement",

    "Merits: Safe and odorless chemicals for indoor use",
    "Convenience: No need to vacate premises during treatment",
    "Integrated Service: Covers multiple crawling pests together",
    "Prevention Advice: Sealing gaps and reducing moisture",

    "Service Plan: Customized based on inspection",
    "Annual Plan: 3 scheduled treatments with free follow-ups",
    "Flexibility: Single service can be upgraded to yearly contract"
  ]
},

termite: {
  title: "Termite Management",
  items: [
    "Overview: Termites live underground and silently damage homes by feeding on wood, paper, and valuables",

    "Damage Risk: They travel through walls using mud tubes and spread like roots, often unnoticed until serious damage occurs",

    "Colony Formation: A colony starts when a queen and king pair after swarming and build a nest in moist soil",
    "Egg Production: Queen lays thousands of eggs daily, producing millions over her lifetime",

    "Workers: Build tunnels, collect food, and maintain the colony",
    "Soldiers: Protect the colony using strong jaws or chemical defense",
    "Reproductive Termites: Winged termites form new colonies after swarming",

    "Favorable Conditions: Moisture, damp wood, humidity, and hidden areas",
    "Food Source: Wood, paper, cardboard, and other cellulose materials",
    "Shelter: Cluttered, dark, and untreated spaces support growth",

    "Infestation Areas: Walls, furniture, books, basements, and storage spaces",
    "Entry Points: Cracks in walls, floors, doors, and windows",
    "Mud Tubes: Underground tunnels connecting nests to food sources",
    "Infested Materials: Bringing untreated wood indoors spreads termites",

    "Pre-Construction Treatment: Chemical barrier applied during construction to block termite entry",
    "Long-Term Protection: Prevents termites from entering the structure permanently",

    "Post-Construction Treatment: Drill & Fill method creates a chemical barrier in walls",
    "Wood Treatment: Spraying infested wood to eliminate termites",

    "Merits: Precise drilling technique ensures deep protection",
    "Warranty: Free service within warranty period if termites reappear",
    "Comprehensive Solution: Covers both prevention and active infestation control"
  ]
},

rodent: {
  title: "Rodent Management",
  items: [
    "Overview: Rodents like rats and mice are common pests that damage property and spread diseases",

    "Damage Risk: They gnaw on wires, electronics, furniture, clothes, and books",
    "Health Risk: Contaminate food with saliva, fur, and droppings causing diseases",

    "Origin: Rodents exist naturally and adapt easily to human environments",
    "Entry Points: Cracks, gaps, drains, pipes, and open doors or windows",

    "Rapid Growth: Female rodents produce multiple litters yearly (6–12 babies each)",
    "Infestation Risk: Small presence can quickly turn into large infestation",

    "Why They Infest: Food sources like grains, garbage, pet food, and leftovers",
    "Water Sources: Leaks, damp areas, and standing water",
    "Shelter: Dark, hidden places like basements, attics, and wall cavities",

    "Movement: Enter through cracks and gaps in walls",
    "Spread: Use pipes, wires, and drains as pathways",
    "Ability: Excellent climbers and jumpers",

    "Inspection: Full inspection of kitchens, basements, ceilings, and storage areas",

    "Glue Traps: Sticky boards placed in rodent paths",
    "Rodent Bait: Chemical bait cakes to attract and eliminate rodents",
    "Cages & Traps: Mechanical and humane trapping methods",

    "Prevention: Sealing gaps, repairing cracks, and improving hygiene",

    "Merits: Eco-friendly and non-toxic solutions",
    "Professional Service: Effective and reliable pest control methods",

    "Pre-Construction Plan: 10-year warranty",
    "Post-Construction Plan: 2-year warranty with regular visits",
    "Customized Service: Based on customer needs"
  ]
},

mosquito: {
  title: "Mosquito Management",
  items: [
    "Overview: Mosquitoes are dangerous pests that spread diseases like malaria, dengue, chikungunya, and brain fever",

    "Breeding Areas: Stagnant water such as ponds, drains, septic tanks, tires, containers, and gardens",

    "Egg Laying: Female mosquitoes lay eggs in even small amounts of standing water",
    "Life Cycle: Egg → Larva → Pupa → Adult mosquito",
    "Growth Speed: Life cycle completes within 7–10 days in favorable conditions",

    "Activity: Adult mosquitoes are attracted to body heat and carbon dioxide",
    "Biting: Females bite humans and animals to feed on blood for egg production",

    "Why They Infest: Presence of stagnant water and damp surroundings",
    "Shelter: Dark, cool, and humid areas like bathrooms, basements, and behind curtains",

    "Entry Points: Open windows and doors without screens",
    "Outdoor Sources: Gardens, drains, water tanks, and surroundings",
    "Indoor Sources: Coolers, flower pots, trays, and stored water",

    "Inspection: Complete inspection of site and surroundings to locate breeding areas",

    "Indoor Spraying: Treatment in corners, windows, doors, and dark resting areas",
    "Outdoor Spraying: Applied on walls, bushes, gardens, and drains",
    "Larval Control: Special larvicide treatment to stop mosquito growth at early stage",

    "Merits: Stops mosquitoes at larval stage to break life cycle",
    "Coverage: Complete indoor and outdoor protection",
    "Prevention Advice: Guidance to remove water sources and maintain hygiene",

    "Service Option: One-time service for immediate relief",
    "Annual Plan: 3 scheduled treatments for year-round protection"
  ]
},

bedbug: {
  title: "Bed Bug Management",
  items: [
    "Overview: Bed bugs are small parasitic insects that feed on human blood, mainly at night",

    "Common Places: Found in transport, hospitals, hotels, hostels, and homes",
    "Spread: Travel through luggage, clothes, furniture, and personal items",

    "Health Issues: Cause itching, red spots, allergies, and disturbed sleep",

    "Entry: Enter homes by hitchhiking on luggage, used furniture, and clothes",

    "Hiding Spots: Mattress seams, bed frames, sofas, cracks, behind frames, and electrical outlets",
    "Behavior: Nocturnal insects that hide during the day",

    "Rapid Growth: A female lays 200–500 eggs in lifetime",
    "Egg Hatch: Eggs hatch within 6–10 days",
    "Growth Cycle: Nymphs become adults in 5–7 weeks",

    "Infestation Areas: Beds, pillows, carpets, curtains, furniture, and wall cracks",

    "Spread Inside: Move through walls, vents, and between rooms",

    "Inspection: Detailed checking of beds, sofas, furniture, and infested areas",

    "Spray Treatment: Targeted chemical spraying on all hiding spots",
    "Complete Coverage: Treatment of surrounding areas to prevent survival",

    "Follow-Up: Re-inspection to ensure full removal",

    "Merits: Use of government-approved safe chemicals",
    "Thorough Process: Complete inspection and treatment",
    "Fast Relief: Quick solution for itching and sleepless nights",

    "Service: One-time treatment based on infestation level",
    "Follow-Up: Additional visits as required"
  ]
}

,


energyMeter: {
  title: "Replacement / Shifting of Single Phase Energy Meter",
  items: [
    "Overview: Professional replacement and shifting of single phase energy meters for residential and small commercial setups",

    "Purpose: Ensure accurate electricity measurement and safe power distribution",

    "When Replacement is Needed: Faulty meter, incorrect readings, old or damaged meter",

    "Shifting Need: Relocation due to renovation, safety concerns, or change in wiring layout",

    "Meter Types: Digital and smart single phase energy meters",

    "Site Inspection: Assessment of existing meter position, wiring, and load requirements",

    "Load Check: Verification of electrical load to ensure proper meter compatibility",

    "Permission Support: Guidance for required approvals from electricity board (if applicable)",

    "Removal Process: Safe disconnection and removal of existing meter",

    "Installation Process: Proper installation of new meter at suitable location",

    "Shifting Process: Careful relocation and reconnection of existing meter",

    "Wiring Setup: Proper connection with main supply, distribution board, and earthing",

    "Safety Measures: Compliance with electrical safety standards and regulations",

    "Sealing & Testing: Proper sealing and testing to ensure accurate readings",

    "Why Shifting is Needed: Better accessibility, safety improvement, or structural changes",

    "Common Issues Solved: Faulty readings, loose connections, exposed wiring, and overheating",

    "Merits: Accurate billing, improved safety, and better system reliability",

    "Coverage: Complete removal, installation, shifting, and testing support",

    "Prevention Advice: Regular inspection and avoiding overloading of circuits",

    "Service Option: One-time replacement or shifting service",

    "Annual Plan: Periodic inspection and maintenance support"
  ]
},


msedclElectrification: {
  title: "MSEDCL Work – Electrification to Dedicated Distribution Facility",
  items: [
    "Overview: End-to-end electrification services for dedicated distribution facilities as per MSEDCL norms and guidelines",

    "Purpose: Provide reliable and dedicated power supply for industrial, commercial, or high-load consumers",

    "Scope of Work: Complete execution from planning, approvals, installation, and commissioning",

    "Load Requirement: Assessment of sanctioned load and future expansion needs",

    "Site Survey: Detailed inspection of project site and nearby power infrastructure",

    "Design & Planning: Preparation of electrical layout, SLD (Single Line Diagram), and system design",

    "Approvals: Coordination and documentation support for MSEDCL approvals and permissions",

    "Dedicated Feeder Setup: Installation of exclusive feeder line for uninterrupted power supply",

    "Transformer Installation: Supply and installation of suitable capacity transformer",

    "HT/LT Line Work: Erection of High Tension (HT) and Low Tension (LT) lines",

    "Cabling Work: Laying of underground or overhead cables as per requirement",

    "Metering System: Installation of HT/LT metering panels with proper protection",

    "Panel Installation: Setup of control panels, distribution boards, and protection systems",

    "Earthing System: Proper earthing and lightning protection setup",

    "Safety Compliance: Adherence to MSEDCL and electrical safety standards",

    "Testing & Commissioning: Pre-commissioning checks and final energization",

    "Why Dedicated Facility: Ensures stable voltage, reduced interruptions, and better load management",

    "Applications: Industries, factories, commercial complexes, hospitals, and large establishments",

    "Merits: Reliable power supply, improved efficiency, and reduced downtime",

    "Coverage: Complete turnkey electrification solution including approvals and execution",

    "Prevention Advice: Regular inspection, load balancing, and maintenance of electrical systems",

    "Service Option: One-time project execution",

    "Annual Plan: Ongoing maintenance and support for electrical infrastructure"
  ]
},


solarHighMast: {
  title: "Erecting & Commissioning of 12.5m Solar High Mast Pole",
  items: [
    "Overview: Complete erection and commissioning of 12.5 meter solar high mast lighting systems for large outdoor areas",

    "Purpose: Provide powerful and energy-efficient lighting using solar energy for public and commercial spaces",

    "Applications: Roads, highways, parking areas, industrial zones, grounds, and public places",

    "Site Survey: Inspection of location for soil condition, space availability, and sunlight exposure",

    "Foundation Work: Construction of strong RCC foundation to support high mast structure",

    "Pole Installation: Erection of 12.5m high mast pole with proper alignment and stability",

    "Solar Panel Setup: Installation of solar panels at optimal angle for maximum sunlight absorption",

    "Battery System: Installation of battery for energy storage and backup during night hours",

    "LED Lighting: Mounting of high-power LED luminaires for wide area illumination",

    "Controller Setup: Installation of solar charge controller for system regulation and protection",

    "Wiring & Cabling: Proper electrical connections between panels, battery, and lighting system",

    "Earthing System: Effective earthing for safety and lightning protection",

    "Safety Measures: Compliance with structural and electrical safety standards",

    "Commissioning: Testing and activation of complete system for proper functioning",

    "Performance Check: Verification of lighting output, battery backup, and charging efficiency",

    "Why Solar High Mast: Saves electricity cost and provides reliable lighting in remote areas",

    "Common Benefits: Low maintenance, eco-friendly, and long operational life",

    "Coverage: Complete supply, installation, and commissioning support",

    "Maintenance Advice: Periodic cleaning of panels and battery health check",

    "Service Option: One-time installation service",

    "Annual Plan: Routine maintenance and inspection for long-term performance"
  ]
},

electrificationServices: {
  title: "Residential & Commercial Electrification Services",
  items: [
    "Overview: Complete electrical solutions for residential homes and commercial establishments from wiring to final commissioning",

    "Purpose: Ensure safe, efficient, and reliable power distribution for all types of properties",

    "Scope of Work: End-to-end electrification including design, installation, testing, and maintenance",

    "Site Inspection: Detailed assessment of layout, load requirements, and electrical planning",

    "Load Calculation: Proper estimation of electrical load to design a balanced system",

    "Electrical Design: Preparation of wiring layout, distribution plan, and load balancing",

    "Wiring Work: High-quality concealed and surface wiring using standard materials",

    "Panel Installation: Setup of distribution boards (DB), MCBs, RCCBs, and control panels",

    "Switches & Fixtures: Installation of switches, sockets, lights, fans, and other electrical fixtures",

    "Earthing System: Proper grounding to ensure safety and protection from electrical faults",

    "Safety Measures: Compliance with electrical safety standards and fire prevention norms",

    "Power Backup Integration: Provision for inverter or generator connection (if required)",

    "Lighting Solutions: Indoor and outdoor lighting setup for efficiency and aesthetics",

    "Commercial Setup: Electrification for offices, shops, showrooms, and industrial units",

    "Renovation Work: Rewiring and upgrading old or unsafe electrical systems",

    "Troubleshooting: Identification and fixing of faults, short circuits, and overload issues",

    "Testing & Commissioning: Final testing to ensure system safety and performance",

    "Why Professional Electrification: Reduces risk, improves efficiency, and ensures long-term reliability",

    "Merits: Safe installation, optimized power usage, and durable electrical system",

    "Coverage: Complete installation, repair, and upgrade services",

    "Prevention Advice: Regular inspection, avoiding overloading, and timely maintenance",

    "Service Option: One-time electrification or repair service",

    "Annual Plan: Periodic maintenance and inspection support"
  ]
},





solarInstallation: {
  title: "Installation / Shifting of Solar Panels",
  items: [
    "Overview: Professional installation and relocation of solar panel systems for residential, commercial, and industrial use",

    "Site Assessment: Detailed inspection of rooftop or ground space to determine suitability and panel positioning",

    "Sunlight Analysis: Evaluation of sunlight exposure, shadow-free areas, and optimal panel angle for maximum efficiency",

    "System Design: Customized solar system planning based on energy consumption and available space",

    "Installation Process: Secure mounting of solar panels with proper alignment and structural safety",

    "Electrical Setup: Connection of inverter, wiring, earthing, and integration with main power supply",

    "Safety Measures: Use of standard safety practices to ensure system durability and user protection",

    "Shifting Service: Safe dismantling and reinstallation of solar panels when relocating homes or offices",

    "Panel Handling: Careful removal and transport to avoid damage during shifting",

    "Reinstallation: Proper setup at new location with alignment and performance check",

    "Why Shifting is Needed: Change of property, renovation, or better sunlight availability",

    "Mounting Types: Rooftop, ground-mounted, and elevated structures based on requirement",

    "Inspection: Complete system check including panels, inverter, wiring, and connections",

    "Performance Testing: Ensuring optimal energy generation after installation or shifting",

    "Cleaning & Maintenance: Basic cleaning and maintenance guidance for better efficiency",

    "Merits: Saves electricity cost and ensures long-term energy benefits",

    "Coverage: Complete installation, shifting, and system setup support",

    "Prevention Advice: Tips to maintain panels, avoid shading, and ensure regular cleaning",

    "Service Option: One-time installation or shifting service",

    "Annual Plan: Regular maintenance and inspection plans for consistent performance"
  ]
},

solarInverter: {
  title: "Replacement / Upgradation of Solar Inverters",
  items: [
    "Overview: Professional replacement and upgradation of solar inverters to improve system efficiency and reliability",

    "Purpose: Ensures smooth conversion of solar energy from DC to usable AC power",

    "When Replacement is Needed: Old, faulty, or underperforming inverter affecting energy output",

    "Upgrade Need: Switching to advanced inverters with better efficiency, monitoring, and backup features",

    "Types Supported: String inverters, microinverters, and hybrid inverters",

    "Site Inspection: Complete check of existing inverter setup, wiring, and compatibility",

    "Load Analysis: Evaluation of power usage to recommend suitable inverter capacity",

    "Inverter Selection: Choosing energy-efficient and compatible inverter models",

    "Removal Process: Safe disconnection and removal of old inverter system",

    "Installation Process: Proper mounting and connection of new inverter with safety standards",

    "Electrical Integration: Wiring, earthing, and connection with solar panels and main supply",

    "System Compatibility: Ensuring seamless integration with existing solar panels and batteries",

    "Testing: Performance testing and output verification after installation",

    "Monitoring Setup: Configuration of smart monitoring apps or systems (if supported)",

    "Why Upgrade: Higher efficiency, better performance, improved safety, and advanced features",

    "Common Issues Solved: Low output, frequent shutdowns, overheating, and system errors",

    "Merits: Improves energy generation and extends overall solar system life",

    "Coverage: Complete removal, installation, and system optimization",

    "Prevention Advice: Regular maintenance and timely upgrades to avoid system failures",

    "Service Option: One-time inverter replacement or upgrade service",

    "Annual Plan: Routine inspection and maintenance for long-term performance"
  ]
},

rooftopSolar: {
  title: "Installation of Rooftop Solar Systems",
  items: [
    "Overview: Complete installation of rooftop solar systems for homes, offices, and commercial buildings",

    "Purpose: Generate clean and renewable electricity directly from sunlight to reduce power bills",

    "Site Survey: Detailed inspection of rooftop area to assess space, structure, and feasibility",

    "Sunlight Analysis: Evaluation of sun exposure, shading, and direction for optimal panel placement",

    "System Design: Customized solar solution based on energy consumption and roof size",

    "Capacity Planning: Recommendation of suitable system size (kW) for maximum efficiency",

    "Mounting Structure: Installation of durable and weather-resistant mounting systems",

    "Panel Installation: Proper alignment and fixing of solar panels for maximum sunlight capture",

    "Inverter Setup: Installation and configuration of inverter for DC to AC conversion",

    "Electrical Wiring: Safe wiring, earthing, and connection to main distribution board",

    "Net Metering Support: Assistance with net metering process (if applicable)",

    "Safety Measures: Use of high-quality materials and adherence to safety standards",

    "Testing & Commissioning: System performance testing and activation",

    "Why Rooftop Solar: Saves electricity cost, reduces dependency on grid, and is eco-friendly",

    "Common Applications: Residential homes, apartments, offices, factories, and institutions",

    "Inspection: Final inspection to ensure system performance and safety compliance",

    "Merits: Long-term savings, low maintenance, and sustainable energy solution",

    "Coverage: End-to-end installation including design, setup, and support",

    "Maintenance Advice: Regular cleaning and periodic checks for optimal performance",

    "Service Option: One-time installation service",

    "Annual Plan: Regular maintenance and performance monitoring plans"
  ]
},

solarMaintenance: {
  title: "Maintenance / Repair of Existing Solar Systems",
  items: [
    "Overview: Complete maintenance and repair services to ensure optimal performance of existing solar systems",

    "Purpose: Maintain efficiency, prevent breakdowns, and extend system lifespan",

    "System Inspection: Detailed check of solar panels, inverter, wiring, and connections",

    "Performance Check: Analysis of energy generation and identification of output loss",

    "Fault Detection: Diagnosis of issues like low output, inverter errors, or system shutdowns",

    "Panel Cleaning: Removal of dust, dirt, and debris to improve sunlight absorption",

    "Wiring Check: Inspection of cables, connectors, and earthing for safety and efficiency",

    "Inverter Service: Troubleshooting, repair, or replacement of faulty inverters",

    "Battery Check: Inspection and maintenance of batteries (if installed)",

    "Repair Work: Fixing damaged panels, loose connections, and electrical faults",

    "Replacement: Changing defective components such as MC4 connectors, cables, or fuses",

    "Why Maintenance is Needed: Dust, weather impact, aging components, and improper usage",

    "Common Issues: Reduced efficiency, sudden shutdowns, overheating, and error alerts",

    "Safety Measures: Ensuring system is safe from electrical hazards and fire risks",

    "Testing: Post-repair testing to verify system performance and output",

    "System Optimization: Adjustments to improve energy generation efficiency",

    "Merits: Improves performance, reduces energy loss, and increases system life",

    "Coverage: Complete inspection, cleaning, repair, and optimization",

    "Prevention Advice: Regular cleaning, periodic checks, and timely servicing",

    "Service Option: One-time maintenance or repair service",

    "Annual Plan: Scheduled maintenance visits for consistent performance"
  ]
},


founder: {
  title: "Atul Urkude - Founder and Director",
  items: [
    `A highly motivated and enthusiastic professional with more than 7+ years of experience in the field of Marketing and Business Development, he has consistently demonstrated strong leadership, strategic thinking, and a passion for growth. His ability to identify market opportunities and build sustainable business models has been a key factor in his success.

In 2018, he came up with the innovative idea of multiservices to cater to both corporates and individual clients. With a vision to provide reliable and comprehensive solutions under one roof, he established Shul Ventures Pvt. Ltd., aiming to bridge the gap between quality service providers and customer needs.

Under his dynamic leadership, the organization expanded into multiple sectors including Manufacturing, Pest Control, Training and Placement, Electrical Services, Gardening, and Housekeeping Services. He focused on building strong operational systems and ensured that each service vertical maintained high standards of quality and efficiency.

He brought together skilled and experienced professionals for each domain, ensuring that clients receive expert-driven solutions in a streamlined and effective manner. His approach emphasizes innovation, customer satisfaction, and continuous improvement.

With a forward-thinking mindset, he continues to explore new opportunities, adopt modern technologies, and strengthen the company’s presence in diverse industries. His core vision remains centered on delivering excellence, creating long-term value for clients, and establishing Shul Ventures as a trusted and versatile service provider in the market.`
  ]
}
,

prashant: {
  title: "Prashant Chunarkar - Manager, Business Development",
  items: [
    `A young and dynamic professional known for his strong connection with people, society, and diverse business sectors, he brings energy, adaptability, and a growth-oriented mindset to his role. His interpersonal skills and ability to build meaningful relationships have contributed significantly to business expansion and client satisfaction.

A graduate in Bachelor of Commerce (B.Com), he possesses more than 3+ years of hands-on experience in Sales and Marketing. Throughout his career, he has demonstrated a keen understanding of market trends, customer behavior, and strategic planning, which enables him to effectively drive business development initiatives.

Currently associated with Shul Ventures Pvt. Ltd. as a Manager in Business Development, he actively contributes to all aspects of business growth, including client acquisition, relationship management, and service expansion. His proactive approach and dedication ensure smooth execution of business strategies and consistent performance improvement.

He has successfully built and managed a team of skilled professionals specializing in pest control services. His team, trained under experienced industry experts, is committed to delivering effective, safe, and reliable pest control solutions. Under his leadership, the team continues to maintain high standards of service quality and customer satisfaction.

With a continuous focus on learning and improvement, he strives to enhance operational efficiency, expand service reach, and contribute to the overall growth and success of the organization.`
  ]
}
,



bhavana: {
  title: "Bhavana Bhoyar - Trainee Software Developer",
  items: [
    `Bhavana Bhoyar has completed her Bachelor of Engineering (B.E.) in Information Technology from Guru Nanak Institute of Engineering and Technology. She has a strong foundation in software development and a keen interest in learning new technologies.

She became a part of Shul Ventures Pvt. Ltd. in August 2019 and has been contributing to the organization as a Software Developer. She is involved in developing and supporting various projects, ensuring efficient and reliable solutions.

Along with her development work, she also guides students in internship programs, helping them gain practical knowledge and industry exposure. Her dedication, willingness to learn, and supportive nature make her a valuable member of the team.

She continuously works on improving her technical skills and stays updated with the latest industry trends and tools. Her ability to adapt to new challenges and her commitment to delivering quality work contribute significantly to the success of the projects she handles.

With a positive attitude and a growth mindset, she aims to build a strong career in the IT industry while contributing effectively to organizational development.`
  ]
},


rakesh: {
  title: "Rakesh Donode - Founder, Teerth Electrical Constructions",
  items: [
    `"A small venture that transformed into a successful entrepreneurial journey."

Rakesh Donode is a dedicated and experienced professional who stepped into entrepreneurship in 2017 by establishing his firm, Teerth Electrical Constructions. He holds a degree in Electrical Engineering from Nagpur University (2007) and has over 9 years of experience working in the corporate sector, which laid a strong foundation for his business.

Teerth Electrical Constructions has grown into a well-recognized organization providing reliable electrical services across various sectors including MSEB, PWD, and other industrial and government projects. His technical expertise and industry experience have played a crucial role in delivering quality and efficient solutions.

After gaining extensive industrial experience, he decided to start his own venture with a vision to provide dependable and professional electrical services. Through consistent efforts and strong team support, the company has earned a reputable name in the electrical industry.

With increasing demand from residential clients, Teerth Electrical expanded into local household electrification services, ensuring safe and efficient solutions for homes as well.

The company focuses on quick response time, high-quality workmanship, and customer satisfaction. Teerth Electrical offers a wide range of services, unified by its commitment to delivering reliable solutions and maintaining long-term client relationships.`
  ]
}
,

suresh: {
  title: "Suresh Walwand - Founder, Shiv Pest Control",
  items: [
    `Suresh Walwand is the founder of Shiv Pest Control and a seasoned professional in the pest management industry. He graduated from Amravati University in 2006, and during his academic years, he began working with a pest control company, gaining practical exposure and hands-on experience in the field.

With more than 7 years of industry experience, he developed strong expertise in pest control solutions, customer handling, and service execution. His deep understanding of the industry motivated him to start his own venture, and in 2011, he established Shiv Pest Control with a vision to provide reliable and effective pest management services.

Over the years, Shiv Pest Control has earned a strong reputation and recognition in the pest control industry. The company primarily operates in Aurangabad, Pune, and Jalna, consistently delivering high-quality services and effective pest control solutions to its clients.

With a commitment to growth and expansion, Shiv Pest Control collaborated with Shul Ventures Pvt. Ltd. to extend its services across the Vidarbha region. This partnership focuses on reaching the right customers and providing efficient, safe, and long-lasting pest control solutions.

The company continues to focus on customer satisfaction, professional service, and innovative approaches to pest management, ensuring a pest-free and healthy environment for its clients.`
  ]
}
,

priyanka: {
  title: "Priyanka Selote - Executive, HR and Finance",
  items: [
    `Priyanka Selote is a dedicated and detail-oriented professional currently pursuing her MBA from Nagpur University with a specialization in Human Resources and Finance. Her academic background equips her with strong knowledge in both people management and financial operations.

She joined Shul Ventures Pvt. Ltd. in April 2019 and has been an important part of the organization since then. She works as an Executive in HR and Finance, contributing to both strategic and operational aspects of the business.

In her role, she manages various administrative responsibilities including client management, coordination, and overall office operations. She also plays a key role in client support, ensuring smooth communication and maintaining strong professional relationships.

Her ability to handle multiple responsibilities efficiently, along with her organizational skills and commitment to excellence, makes her a valuable asset to the company.

With a continuous focus on learning and professional growth, she aims to strengthen her expertise in HR and finance while contributing to the overall success and development of the organization.`
  ]
}



  };

  const content = data[course];
  if (!content) return;

  title.innerText = content.title;
  list.innerHTML = "";

  content.items.forEach(item => {
    let li = document.createElement("li");
    li.innerText = item;
    list.appendChild(li);
  });

 modal.classList.add("show");
}

function closeModal() {
  document.getElementById("courseModal").style.display = "none";
}

window.onclick = function(event) {
  const modal = document.getElementById("courseModal");
  if (event.target === modal) {
    modal.classList.remove("show");
  }
};



// Works for multiple Read More sections independently
const readMoreButtons = document.querySelectorAll('.read-more-btn');

readMoreButtons.forEach(button => {
  button.addEventListener('click', () => {
    const section = button.parentElement; // the wrapper div
    const dots = section.querySelector('.dots');
    const moreText = section.querySelector('.more-content');

    if (moreText.style.display === 'none' || moreText.style.display === '') {
      moreText.style.display = 'inline';
      dots.style.display = 'none';
      button.textContent = 'Read Less';
    } else {
      moreText.style.display = 'none';
      dots.style.display = 'inline';
      button.textContent = 'Read More';
    }
  });
});



 /* =========================
   MENU TOGGLE
========================= */
document.addEventListener("DOMContentLoaded", function () {

  const toggle = document.querySelector(".menu-toggle");
  const nav = document.querySelector(".nav-links");

  if (toggle) {
    toggle.addEventListener("click", () => {
      nav.classList.toggle("active");
      toggle.classList.toggle("active");
    });
  }

});


/* =========================
   LOAD JOBS (GET JOBS)
========================= */
document.addEventListener("DOMContentLoaded", function () {

  const container = document.getElementById("jobs-container");

  if (!container) return;

  fetch("get_jobs.php")
    .then(res => res.json())
    .then(data => {

      container.innerHTML = "";

      if (data.length === 0) {
        container.innerHTML = "<p>No jobs available</p>";
        return;
      }

      data.forEach(job => {

        const card = document.createElement("div");
        card.className = "job-card";

        card.innerHTML = `
          <h3>${job.job_profile}</h3>
          <p><b>Experience:</b> ${job.position_level || '-'}</p>
          <p><b>Skills:</b> ${job.skills_required || '-'}</p>
          <p><b>Qualification:</b> ${job.education || '-'}</p>
          <button class="apply-btn">Apply Now</button>
        `;

        card.querySelector(".apply-btn").addEventListener("click", () => {
          openForm(job.job_profile);
        });

        container.appendChild(card);
      });

    })
    .catch(() => {
      container.innerHTML = "<p>Failed to load jobs</p>";
    });

});


function openForm(title) {
  const modal = document.getElementById("jobFormModal");
  const jobTitle = document.getElementById("jobTitle");
  const input = document.getElementById("job_input");

  jobTitle.innerText = "Apply for " + title;
  input.value = title;

   modal.classList.add("show");   // ✅ FIX
}

/* =========================
   CLOSE JOB FORM MODAL (FIX)
========================= */
window.closeForm = function () {
  document.getElementById("jobFormModal").classList.remove("show");
}



document.addEventListener("DOMContentLoaded", function () {

  const form = document.getElementById("jobForm");
  const msg = document.getElementById("formMessage");

  form.addEventListener("submit", function (e) {

    e.preventDefault();

    const formData = new FormData(form);

    msg.innerHTML = "Submitting...";

    fetch("apply.php", {
      method: "POST",
      body: formData
    })

    .then(res => res.json())

    .then(data => {

      if (data.status === "success") {

        document.getElementById("successPopup").style.display = "flex";

        msg.innerHTML = data.message;

        form.reset();

        
      } else {

        msg.innerHTML = "❌ " + data.message;

      }

    })

    .catch(error => {

      console.log(error);

      msg.innerHTML = "❌ Server error!";

    });

  });

});




/* =========================
   SUCCESS POPUP
========================= */
function closeSuccess() {
  document.getElementById("successPopup").style.display = "none";

  setTimeout(() => {
          window.location.href = "jobs.html";
        }, 2000);

}



/* =========================
   CLICK OUTSIDE MODAL CLOSE
========================= */
window.addEventListener("click", function (event) {
  const modal = document.getElementById("jobFormModal");

  if (event.target === modal) {
    modal.style.display = "none";
  }
});



// =========================
// loader// 

// Inject loader automatically on every page
document.addEventListener("DOMContentLoaded", function () {

  // Create loader HTML
  const loaderHTML = `
    <div id="loader">
      <svg class="loader-svg" viewBox="0 0 100 100">
        <g class="rotate">
          <path d="M50,50 L50,0 A50,50 0 0,1 95.1,30.9 Z" fill="#fbbc05"/>
          <path d="M50,50 L95.1,30.9 A50,50 0 0,1 79.4,90.5 Z" fill="#ea4335"/>
          <path d="M50,50 L79.4,90.5 A50,50 0 0,1 20.6,90.5 Z" fill="#a142f4"/>
          <path d="M50,50 L20.6,90.5 A50,50 0 0,1 4.9,30.9 Z" fill="#4285f4"/>
          <path d="M50,50 L4.9,30.9 A50,50 0 0,1 50,0 Z" fill="#34a853"/>
        </g>
      </svg>
    </div>
  `;

  // Add loader to page
  document.body.insertAdjacentHTML("afterbegin", loaderHTML);

  const loader = document.getElementById("loader");

  // Hide after 0.5 sec (change if needed)
  setTimeout(() => {
    loader.classList.add("fade-out");

    setTimeout(() => {
      loader.remove();
    }, 400);

  }, 500);
}); 