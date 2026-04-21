<?php
include("../../php/connection.php");

// ── College of Accountancy ──────────────────────────────
$college_location     = '2nd Floor - Gusaling Lacson (GL)';
$college_local_number = 'College Staff: 211';
$college_description  = 'The College of Accountancy at Pamantasan ng Lungsod ng Maynila is dedicated to becoming a leader in accountancy education through its commitment to academic excellence, innovation, and ethical leadership. With a rigorous curriculum, impactful research, strong industry partnerships, and a culture of integrity and social responsibility, the college aims to produce competent and socially responsible accountants who will contribute significantly to national development. The continuous professional growth of faculty and staff ensures that the college remains at the cutting edge of educational and professional practices, living up to its motto: "Excellence. Integrity. Innovation."';
$college_vision       = 'To be a leading institution in accountancy education, recognized for academic excellence, innovation, and ethical leadership, committed to producing competent and socially responsible accountants who will contribute significantly to national development.';
$college_mission      = 'The College of Accountancy at Pamantasan ng Lungsod ng Maynila is dedicated to providing high-quality education that equips students with the knowledge, skills, and ethical grounding necessary for professional success. We aim to foster a learning environment that promotes critical thinking, innovation, and lifelong learning.';
$college_objectives   = "Guided by the college's vision and mission, we commit ourselves:\n• Deliver a rigorous and relevant curriculum that meets the highest academic and professional standards.\n• Engage in impactful research and extension services that address contemporary issues in the field of accountancy.\n• Forge strong partnerships with industry, government, and professional organizations to enhance educational and career opportunities for our students.\n• Cultivate a culture of integrity, accountability, and social responsibility among our students, faculty, and staff.\n• Support the continuous development of our faculty and staff to ensure they remain at the forefront of educational and professional practices.";
$ca = 'CA';

$s1 = mysqli_prepare($con, "UPDATE courses SET college_location=?, college_local_number=?, college_description=?, college_vision=?, college_mission=?, college_objectives=? WHERE college_code=?");
mysqli_stmt_bind_param($s1, 'sssssss', $college_location, $college_local_number, $college_description, $college_vision, $college_mission, $college_objectives, $ca);
mysqli_stmt_execute($s1);
$r1 = mysqli_stmt_affected_rows($s1);

// ── BSA Program ─────────────────────────────────────────
$bsa_desc   = 'The Bachelor of Science in Accountancy (BSA) at Pamantasan ng Lungsod ng Maynila (PLM) is a comprehensive and rigorous academic program designed to develop highly competent accounting professionals. The curriculum integrates theoretical knowledge with practical application to ensure that graduates are well-prepared to meet the demands of the accounting profession.';
$bsa_career = "Graduates of the BSA program have a wide range of career opportunities in various sectors, including public practice, commerce and industry, government, and education. Possible careers and professions include:\n\n• Certified Public Accountant (CPA): Licensed professionals providing audit, tax, and advisory services.\n• Financial Accountant: Responsible for preparing financial statements and ensuring accuracy in financial reporting.\n• Management Accountant: Focuses on internal financial analysis, budgeting, and cost management to support business decisions.\n• Auditor: Conducts audits to evaluate the accuracy and integrity of financial records.\n• Tax Consultant: Provides advice on tax planning, compliance, and strategy to individuals and businesses.\n• Internal Auditor: Assesses the effectiveness of internal controls and risk management processes within an organization.\n• Forensic Accountant: Investigates financial discrepancies and fraud, often working with legal authorities.\n• Financial Analyst: Analyzes financial data to assist in investment decisions and financial planning.\n• Accounting Information Systems Specialist: Manages and implements accounting software and information systems.\n• Budget Analyst: Develops and manages budgets for organizations, ensuring efficient allocation of resources.\n• Academician: Engages in teaching, research, and academic administration in educational institutions.\n• Consultant: Provides expert advice in accounting, finance, and business strategy to various clients.";
$bsa = 'BSA';

$s2 = mysqli_prepare($con, "UPDATE courses SET description=?, career_opportunities=? WHERE course_code=?");
mysqli_stmt_bind_param($s2, 'sss', $bsa_desc, $bsa_career, $bsa);
mysqli_stmt_execute($s2);
$r2 = mysqli_stmt_affected_rows($s2);

// ── College of Architecture and Sustainable Built Environment ──
$casbe_location    = '2nd Floor - Gusaling Corazon Aquino (GCA)';
$casbe_local       = 'College Staff: 215';
$casbe_desc        = 'The College of Architecture and Sustainable Built Environment is an academic unit of PLM that provides quality education for the next generation of architecture and construction professionals to become leaders in the sustainable design, construction, and management of the built environment. It offers a comprehensive approach that integrates traditional architecture with a focus on environmental and social responsibility.';
$casbe_history     = "The Bachelor of Science in Architecture program was first offered at the university in 1987. The basic objective of opening this unique 5-year discipline was to extend equal opportunity to economically challenged but deserving students of Manila. This, along with Fine Arts, was initially attached to the College of Engineering and Technology, among the largest colleges on the campus even until today.\n\nThe program was eventually given its own academic unit, the College of Architecture and Urban Planning (CAUP), and was officially recognized as a separate college on January 10, 2001 by virtue of BOR Resolution No. 2272. Together with the Manila local government's vision to transform an urban decay city into a vibrant, world-class urban tourist destination; this thrust on urban renewal paved the way to institutionalize sustainable programs by way of a permanent home centered on urban planning and design.\n\nIn light of the industry shift to sustainable design and construction, the college was renamed as the College of Architecture and Sustainable Built Environments in January 2024 to align its goals and objectives towards sustainable development and the flexibility to offer new programs that would meet them. For many academic years this College prides itself of reaping numerous honors and citations for PLM, both in local and national arena. These achievements were a result of a strong and continuing partnership built around a team spirit nurtured by an inspired studentry and a supportive institution.";
$casbe_vision      = "To become a partner of choice as an active provider of competent industry players in today's built-environment market and be a catalyst for sustainable change in the City of Manila and beyond.";
$casbe_mission     = 'Create an environment mutually beneficial to students and the institution by providing opportunities of cooperation while in constant adherence to excellence in professionalism, creativity, team work, and leadership to foster their awareness on professional responsibilities towards sustainability, the socio-cultural strata, environmental values, built heritage conservation, technical research and collaboration with allied-professions in developing design leadership.';
$casbe_objectives  = "Guided by the college's vision and mission, we commit ourselves to:\n• The College is committed to educate and nurture competent future architects and built environment professionals as partners to nation building in producing excellence-oriented and morally upright students and future professionals prepared for leadership role in the industry.\n• The College is dedicated to instill the values of critical thinking and academic excellence while training future professionals to understand sustainable practices anchored to protect the built-environment, for the common welfare, and for benefit of the City of Manila.\n• To imbue upon graduates a direction towards a genuine socially-responsive and highly-innovative mindset that are well-grounded on the technical aspects, and updated on the technology and prevailing conditions.\n• To provide in-depth understanding of the profession and the role of built environment professionals in the society and nation building.";
$casbe = 'CASBE';

$s3 = mysqli_prepare($con, "UPDATE courses SET college_location=?, college_local_number=?, college_description=?, college_history=?, college_vision=?, college_mission=?, college_objectives=? WHERE college_code=?");
mysqli_stmt_bind_param($s3, 'ssssssss', $casbe_location, $casbe_local, $casbe_desc, $casbe_history, $casbe_vision, $casbe_mission, $casbe_objectives, $casbe);
mysqli_stmt_execute($s3);
$r3 = mysqli_stmt_affected_rows($s3);

// ── BS Arch Program ─────────────────────────────────────
$arch_desc   = 'The Bachelor of Science in Architecture is a five (5)-year undergraduate program designed to equip students with a comprehensive understanding of architectural theories, design principles, building technologies, and historical contexts. Through courses in design, visualization, structures, and sustainability, this program prepares graduates for a career in architecture and related fields in the construction industry.';
$arch_career = "Careers for professionals:\n• Architectural Design, Pre-design services for architecture\n• Housing\n• Physical Planning\n• Urban Design\n• Community Architecture\n• Facility Planning\n• Construction Technology\n• Construction Management\n• Project Management\n• Building Administration and Maintenance\n• Real Estate Development\n• Architectural Education\n• Research and Development\n• Restoration/Conservation\n• Design-build Services\n\nCareers for graduates:\n• Architectural drafting\n• CADD operator\n• Project Coordinator\n• Project Supervisor/Inspector\n• Project estimator\n• Rendering (manual and electronic)";
$arch = 'BS Arch';

$s4 = mysqli_prepare($con, "UPDATE courses SET description=?, career_opportunities=? WHERE course_code=?");
mysqli_stmt_bind_param($s4, 'sss', $arch_desc, $arch_career, $arch);
mysqli_stmt_execute($s4);
$r4 = mysqli_stmt_affected_rows($s4);

echo "<p>College of Accountancy (CA) updated: <strong>$r1</strong> row(s)</p>";
echo "<p>BSA program updated: <strong>$r2</strong> row(s)</p>";
echo "<p>College of Architecture (CASBE) updated: <strong>$r3</strong> row(s)</p>";
echo "<p>BS Arch program updated: <strong>$r4</strong> row(s)</p>";

// ── College of Engineering ───────────────────────────────
$ce_location    = '3rd Floor - Gusaling Villegas (GV)';
$ce_local       = 'College Staff: 231 | Engineering Laboratory: 232';
$ce_desc        = 'Formerly the College of Engineering and Technology, the College of Engineering is one of the Colleges of the University effective 25 January 2024 after the Management Reorganization of the Pamantasan ng Lungsod ng Maynila.';
$ce_history     = "With the conviction of providing quality education and offering technical manual skills in the field of technology, the College of Engineering was established on July 1, 1969 - six years after the late Mayor Antonio F. Villegas founded the university.\n\nOriginally under the College of Arts and Letters, the main trust of the college was to provide technical, industrial, vocational education to PLM students alongside the humanistic courses to prepare them for promoting out technology under two divisions, namely - the Division of Engineering and Technology which covered the Department of Civil, Mechanical, Electrical, Sanitary, Chemical, Naval and Industrial Engineering and the Division of Technical and Vocational Education which covered the Department of Electronics, Wood Working, Metal Works, Automotive Works, Ceramics, Graphics Arts and Teacher Education in Arts and Trades. Obtaining a degree in this college then, required the student to finish a six-year ladderized program which was later reduced to a five-year scheme during the term of former PLM President Consuelo Blanco who felt the imperative need of the engineering graduates to constitute the country's labor pool.\n\nToday, the College of Engineering stands committed to upholding the legacy conceived by Mayor Villegas and the late Mayor Arsenio H. Lacson by providing its present batch of Engineering students with quality education which is responsive to the needs of the time.";
$ce_vision      = 'The College of Engineering will be the premier college in technological education, research and extension services.';
$ce_mission     = "Guided by this vision, we commit ourselves to:\n• Uphold excellence through curriculum development and teaching, significant advances in knowledge, and services to the community of which we are a part.\n• Nurture students with a technological education of the highest quality that will enable them to be professionally competent, community directed, and God centered individuals.\n• Develop faculty members and staff to be excellent examples in leadership and management.";
$ce_objectives  = "Believing in our mission, we earnestly seek to:\n• Facilitate the achievement of academic goals by regularly reviewing curricular programs, ensuring that they surpass the standards set by governing bodies.\n• Provide a productive environment to facilitate quality research and socially responsive extension service.\n• Develop dynamism among administrators, faculty, student and services personnel, embracing diversities that contribute to the growth of the college.\n• Strengthen our ties with our alumni and industry partners, helping us establish a distinct place in the industry.";
$ce = 'CE';

$s5 = mysqli_prepare($con, "UPDATE courses SET college_location=?, college_local_number=?, college_description=?, college_history=?, college_vision=?, college_mission=?, college_objectives=? WHERE college_code=?");
mysqli_stmt_bind_param($s5, 'ssssssss', $ce_location, $ce_local, $ce_desc, $ce_history, $ce_vision, $ce_mission, $ce_objectives, $ce);
mysqli_stmt_execute($s5);
$r5 = mysqli_stmt_affected_rows($s5);

// ── BSChE Program ────────────────────────────────────────
$bsche_desc   = 'The Bachelor of Science in Chemical Engineering (BSCHE) program at the Pamantasan ng Lungsod ng Maynila (PLM) is a four-year degree program that envisions itself as a center of excellence, a leading institution for research, and with highly qualified faculty members duly recognized for producing technically competent, socially involved, and globally responsive professionals. The program is committed to providing students with a strong theoretical foundation and practical skills essential for success in the field. It also emphasizes the importance of ethical and social responsibility in engineering practice.';
$bsche_obj    = "Key areas of study include:\n• Chemical Engineering Fundamentals: Thermodynamics, fluid mechanics, heat transfer, mass transfer, reaction kinetics, and process control.\n• Chemical Process Design: Development of flowsheets, equipment selection, process optimization, and economic evaluation.\n• Laboratory and Pilot Plant Operations: Hands-on experience in conducting experiments, analyzing data, and scaling up processes.\n• Industrial Safety and Environmental Protection: Emphasis on safe practices, hazard identification, risk assessment, and pollution prevention.";
$bsche_career = "Graduates of the program are well-prepared for careers in various industries, such as:\n• Chemical Manufacturing: Petrochemicals, pharmaceuticals, food processing, and consumer goods.\n• Energy Production: Oil and gas, renewable energy, and nuclear power.\n• Environmental Engineering: Pollution control, waste treatment, and resource recovery.\n• Research and Development: Development of new products and processes.";
$bsche = 'BSChE';

$s6 = mysqli_prepare($con, "UPDATE courses SET description=?, program_objectives=?, career_opportunities=? WHERE course_code=?");
mysqli_stmt_bind_param($s6, 'ssss', $bsche_desc, $bsche_obj, $bsche_career, $bsche);
mysqli_stmt_execute($s6);
$r6 = mysqli_stmt_affected_rows($s6);

// ── BSCpE Program ───────────────────────────────────────
$bscpe_desc   = 'The Bachelor of Science in Computer Engineering (BSCpE) is a program that embodies the science and technology of design, development, implementation, maintenance and integration of software and hardware components in modern computing systems and computer-controlled equipment. This includes knowledge in mathematics and engineering sciences, associated with the broader scope of engineering and beyond that narrowly required for the field. It is a preparation for professional practice in engineering.

Graduates of BS in Computer Engineering should possess the ability to design computers, computer-based systems and networks that include both hardware and software and their integration to solve novel engineering problems, subject to trade-offs involving a set of competing goals and constraints. In this context, "design" refers to a level of ability beyond "assembling" or "configuring" systems.';
$bscpe_career = "Graduates of the BSCpE program can pursue a variety of career paths, including but not limited to the following:\n• Computer Hardware Engineer\n• Systems Engineer\n• Software Engineer\n• Robotics Engineer\n• Back-end Developer\n• Full Stack Developer\n• Network Administrator/Engineer\n• Computer Programmer\n• App Developer\n• IT Security Consultant\n• Software Quality Assurance Engineer\n• Front End Software Engineer\n• Data Engineer/Analyst\n• Technical Support Specialist\n• Multimedia Programmer\n• Web Developer\n• Forensic Computer Analyst\n• Game Developer\n• UX/UI Design Engineer";
$bscpe = 'BSCpE';

$s7 = mysqli_prepare($con, "UPDATE courses SET description=?, career_opportunities=? WHERE course_code=?");
mysqli_stmt_bind_param($s7, 'sss', $bscpe_desc, $bscpe_career, $bscpe);
mysqli_stmt_execute($s7);
$r7 = mysqli_stmt_affected_rows($s7);

// ── BSCE Program ─────────────────────────────────────────
$bsce_desc   = 'The Bachelor of Science in Civil Engineering (BSCE) program is designed to cultivate future engineers with a strong foundation in analytical thinking, teamwork, and technical expertise. This program focuses on the comprehensive understanding required for the design, construction, and maintenance of key infrastructure such as roads, bridges, buildings, water supply systems, irrigation, flood control systems, and ports. Students are trained to integrate new knowledge continuously, ensuring they stay at the forefront of technological and methodological advancements in civil engineering.

The BSCE program offers a broad-based education that prepares graduates to excel in various fields within civil engineering. Specialized tracks include Construction Management and Structural Engineering. Graduates are not only proficient in technical skills but also embody the core values of "Karunungan, Kaunlaran, and Kadakilaan". These values inspire them to contribute productively and ethically to society, engaging in nation-building through careers in academe, research, and industry.';
$bsce_career = "• Civil Engineer\n• Construction Engineer\n• Structural Engineer\n• Geotechnical Engineer\n• Transportation Engineer\n• Water Resources Engineer\n• Construction Manager\n• Quantity Surveyor\n• Project Manager\n• Safety Engineer\n• Environmental Engineer\n• Academician/Professor\n• Research and Development Engineer\n• Urban Planner\n• Consulting Engineer";
$bsce = 'BSCE';

$s8 = mysqli_prepare($con, "UPDATE courses SET description=?, career_opportunities=? WHERE course_code=?");
mysqli_stmt_bind_param($s8, 'sss', $bsce_desc, $bsce_career, $bsce);
mysqli_stmt_execute($s8);
$r8 = mysqli_stmt_affected_rows($s8);

// ── BSEE Program ─────────────────────────────────────────
$bsee_desc   = 'The Bachelor of Science in Electrical Engineering (BSEE) is a program that involves the conceptualization, development, design and application of safe, healthy, ethical, economical and sustainable generation, transmission, distribution and utilization of electrical energy for the benefit of society and the environment through the knowledge of mathematics, physical sciences, information technology and other allied sciences, gained by study, research and practice. Electrical Engineering is one of the broader fields of the engineering disciplines both in terms of the range of problems that fall within its purview and in the range of knowledge required to solve these problems.';
$bsee_career = "The scope of practice of Electrical Engineering is defined in Section 2a of the prevailing Electrical Engineering Law or RA 7920 and pertains to professional services and expertise including but not limited to:\n• Consultation, investigation, valuation and management of services requiring electrical engineering knowledge\n• Design and preparation of plans, specifications and estimates for electric power systems, power plants, power distribution systems including power transformers, transmission lines and network protection, switchgear, building wiring, electrical machines equipment and others\n• Supervision of erection, installation, testing and commissioning of power plants, substations, transmission lines, industrial plants and others\n• Supervision of operation and maintenance of electrical equipment in power plants, industrial plants, watercrafts, electric locomotives and others\n• Supervision in the manufacture and repair of electrical equipment including switchboards, transformers, generators, motors, apparatus and others\n• Teaching of electrical engineering professional courses\n• Taking charge of the sale and distribution of electrical equipment and systems requiring engineering calculations or applications of engineering data\n\nFields of specialization may include, but not limited to:\n• Power system operation and protection\n• Power plant operation and maintenance\n• Advanced electrical systems design and inspection\n• Sales and entrepreneurship\n• Engineering education and research\n• Instrumentation and control systems\n• Construction and project management\n• Software development\n• Electricity market\n• Safety engineering";
$bsee = 'BSEE';

$s9 = mysqli_prepare($con, "UPDATE courses SET description=?, career_opportunities=? WHERE course_code=?");
mysqli_stmt_bind_param($s9, 'sss', $bsee_desc, $bsee_career, $bsee);
mysqli_stmt_execute($s9);
$r9 = mysqli_stmt_affected_rows($s9);

// ── BSECE Program ────────────────────────────────────────
$bsece_desc   = 'The Bachelor of Science in Electronics Engineering (BSECE) program of the Pamantasan ng Lungsod ng Maynila is a comprehensive undergraduate degree that equips students with the knowledge and skills necessary to design, develop, and maintain electronic systems and devices. The program aligns with the country\'s goals to produce globally competitive engineers who can contribute to technological advancements both locally and internationally.

The BSECE program is tailored to meet the specific needs of the Philippine electronics industry, which is a key sector in the country\'s economy. Graduates are prepared to work in various local industries, including telecommunications, semiconductor manufacturing, consumer electronics, and information technology. The program also emphasizes the development of solutions to local challenges, such as improving communication infrastructure in remote areas and advancing local manufacturing capabilities.

The curriculum is designed to meet international standards, ensuring that graduates are competitive in the global job market. The program adheres to guidelines set by the Commission on Higher Education (CHED). The emphasis on modern and emerging technologies, such as IoT (Internet of Things), AI (Artificial Intelligence), and renewable energy systems, prepares graduates for cutting-edge roles in the global electronics industry.

An Electronics Engineer is a professional who conceptualizes, develops, designs, improves and applies safe, healthy, ethical and economic ways in the field of electronics for the benefit of society and environment through the knowledge of basic sciences and mathematics, physical sciences, basic engineering sciences, information technology, electronics engineering and other natural, applied and social sciences, gained by study, research and practice.';
$bsece_career = "Graduates of the BSECE program can pursue a variety of career paths, including but not limited to:\n• Electronics Design Engineer\n• Semiconductor Engineer\n• Telecommunications Engineer\n• Network Engineer\n• Broadcast Engineer\n• Acoustic Engineer\n• Control Systems Engineer\n• Instrumentation Engineer\n• Power Electronics Engineer\n• Biomedical Engineer\n• Test Engineer\n• Data Engineer\n• Software Engineer\n• Robotics Engineer\n• Artificial Intelligence Engineer\n• IoT Engineer\n• Embedded Systems Engineer\n• Technical Sales Engineer\n• Research and Development Engineer\n• Academic and Research Positions";
$bsece = 'BSECE';

$s10 = mysqli_prepare($con, "UPDATE courses SET description=?, career_opportunities=? WHERE course_code=?");
mysqli_stmt_bind_param($s10, 'sss', $bsece_desc, $bsece_career, $bsece);
mysqli_stmt_execute($s10);
$r10 = mysqli_stmt_affected_rows($s10);

// ── BSMfgE Program ───────────────────────────────────────
$bsmfge_desc   = 'The Bachelor of Science in Manufacturing Engineering (BSMfgE) is a program that concerns itself with the understanding and application of engineering procedures in manufacturing processes and production methods. It requires the ability to plan the practices of manufacturing; research; develop tools, processes, machines and equipment; and to integrate the facilities and systems for producing quality product with the optimum expenditure of capital.';
$bsmfge_career = 'The scope of the practice of Manufacturing Engineering includes but is not limited to the following professional services in terms of consultation requiring manufacturing engineering knowledge, skills, and proficiency; design of equipment and processes in a manufacturing industry; operation and maintenance of a manufacturing plant; quality assurance; research and development; and teaching in the academy.';
$bsmfge = 'BSMfgE';

$s11 = mysqli_prepare($con, "UPDATE courses SET description=?, career_opportunities=? WHERE course_code=?");
mysqli_stmt_bind_param($s11, 'sss', $bsmfge_desc, $bsmfge_career, $bsmfge);
mysqli_stmt_execute($s11);
$r11 = mysqli_stmt_affected_rows($s11);

// ── BSME Program ─────────────────────────────────────────
$bsme_desc   = 'The Bachelor of Science in Mechanical Engineering concerns itself with mechanical design, energy conversion, fuel and combustion technologies, heat transfer, materials, noise control and acoustics, manufacturing processes, rail transportation, automatic control, product safety and reliability, solar energy, and technological impacts to society.';
$bsme_career = 'The scope of the practice of Mechanical Engineering pertains to professional services to industrial plants in terms of: consultation requiring mechanical engineering knowledge, skill and proficiency; investigation; estimation and or valuation; planning, preparation of feasibility studies; designing; preparation of specifications; supervision of installation; operation including quality management; and research, among others.';
$bsme = 'BSME';

$s12 = mysqli_prepare($con, "UPDATE courses SET description=?, career_opportunities=? WHERE course_code=?");
mysqli_stmt_bind_param($s12, 'sss', $bsme_desc, $bsme_career, $bsme);
mysqli_stmt_execute($s12);
$r12 = mysqli_stmt_affected_rows($s12);

// ── LL.M. Program ────────────────────────────────────────
$llm_desc   = 'The PLM Graduate School of Law Master of Laws (LL.M.) program is two-year degree program which culminates in a thesis.

Its two-year LL.M. (Master of Laws) program provides students, who already have legal training and experience, with broad latitude to design a course of study that will give them an expanded understanding of law and jurisprudence.

Its curriculum combines the best of traditional courses, as well as legal developments, effective advocacy before the appellate courts, thesis writing, and other allied subjects.

Significantly, the concept of its curriculum also prepares its graduates for the professional challenges globalization entails and thus, will enable them to approach the law in a more universally aware manner.';
$llm = 'LL.M.';

$s13 = mysqli_prepare($con, "UPDATE courses SET description=? WHERE course_code=?");
mysqli_stmt_bind_param($s13, 'ss', $llm_desc, $llm);
mysqli_stmt_execute($s13);
$r13 = mysqli_stmt_affected_rows($s13);

// ── Graduate School of Law College Info ──────────────────
$gsl_location   = '2nd Floor - Gusaling Ejercito (GEE)';
$gsl_local      = 'College Staff: 281';
$gsl_desc       = "The PLM's Graduate School of Law provides legal education to lawyers and non-lawyers who have obtained their first law degree in any college or university in the country offering either a Bachelor of Laws or Juris Doctor program. It aims to attract in its fold lawyers, including judges, prosecutors, practitioners, and those employed in the government and private service. The GSL boasts of its professors who are experts in their respective areas of law, intellectually vigorous, engaged, and committed to quality teaching and high levels of service to students. They come to class with extensive experience having practiced law for the public sector, businesses, private firms, and the Judiciary.";
$gsl_history    = 'On July 7, 2004, the Graduate School of Law was formally launched as the second graduate school of law in the Philippines. On July 29, 2004, the Board of Regents by virtue of BOR Resolution No. 2686 approved the offering of the graduate program of Master of Laws.

The Master of Laws Program (LLM) is offered by the PLM Graduate School of Law as a two-year program, trimestral terms, with strong focus on research that culminates in a thesis. Courses are delivered entirely in English using a multidisciplinary approach by distinguished Jurist and Law Practitioners.';
$gsl_vision     = 'The PLM Graduate School of Law envisions itself as the reflection of an ideal institution of intellectual and highly principled lawyers, with advanced excellent legal training, who will rise to higher callings with commitments to be of service to our country and people.';
$gsl_mission    = "The PLM Graduate School of Law will stress the noble mission of lawyers and judges as well. It will likewise assist in raising the high standards of the legal profession needed in the effective and efficient dispensation of justice for the good of the country, and in contributing meaningful efforts in the pursuit of global peace, and cooperation through law and universal understanding. The PLM Graduate School of Law, inspired by the legacy left by the late President Diosdado M. Macapagal, whose brilliance, integrity and devotion to public duty and service, his concern for the common people, specially the poor and under privileged desirous of achieving higher learning in the midst of economic hardship---envisions to promote and develop educational advancement, leadership and sense of patriotism among members of the Philippine Bar.";
$gsl_objectives = "The Graduate School of Law GSL committed to the mission and vision of the University guided by the values of academic excellence, integrity and social responsibility, and by the principles of Karunungan, Kaunlaran and Kadakilaan. Implicit from its noble mission and vision, the PLM Graduate School of law aims:\n• To develop intellectual expertise in law and jurisprudence among lawyers.\n• To assist in improving public service by stressing the lawyers' mission of upholding justice and truth.\n• To elevate the standards of the legal profession.\n• To enhance the administration of justice for the welfare of the Filipino people, especially the marginalized.";
$gsl = 'GSL';

$s14 = mysqli_prepare($con, "UPDATE courses SET college_location=?, college_local_number=?, college_description=?, college_history=?, college_vision=?, college_mission=?, college_objectives=? WHERE college_code=?");
mysqli_stmt_bind_param($s14, 'ssssssss', $gsl_location, $gsl_local, $gsl_desc, $gsl_history, $gsl_vision, $gsl_mission, $gsl_objectives, $gsl);
mysqli_stmt_execute($s14);
$r14 = mysqli_stmt_affected_rows($s14);

echo "<p>College of Engineering (CE) updated: <strong>$r5</strong> row(s)</p>";
echo "<p>BSChE program updated: <strong>$r6</strong> row(s)</p>";
echo "<p>BSCpE program updated: <strong>$r7</strong> row(s)</p>";
echo "<p>BSCE program updated: <strong>$r8</strong> row(s)</p>";
echo "<p>BSEE program updated: <strong>$r9</strong> row(s)</p>";
echo "<p>BSECE program updated: <strong>$r10</strong> row(s)</p>";
echo "<p>BSMfgE program updated: <strong>$r11</strong> row(s)</p>";
echo "<p>BSME program updated: <strong>$r12</strong> row(s)</p>";
echo "<p>LL.M. program updated: <strong>$r13</strong> row(s)</p>";
echo "<p>Graduate School of Law (GSL) updated: <strong>$r14</strong> row(s)</p>";
echo "<p><a href='admin_accounts.php'>Back to Admin Accounts</a></p>";
