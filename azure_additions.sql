-- Extra settings used by the mobile app Home and Contact pages.
-- It only ADDS rows to the website's existing "settings" table. Nothing in the admin changes.
-- INSERT IGNORE means re-running this file never overwrites values you edited later.
USE andiswa_business;

INSERT IGNORE INTO settings(setting_key, setting_value) VALUES
('tagline', 'WE DESIGN. YOU GROW.'),
('hero_text', 'Andiswa Business Consultation crafts bold visual identities and digital experiences that make South African businesses impossible to ignore.'),
('about_text1', 'Andiswa Business Consultation is a Port Elizabeth based creative agency founded on the belief that exceptional design is the single most powerful investment any business can make. We blend strategic thinking with visual craft to build brands that resonate, differentiate, and endure.'),
('about_text2', 'From early-stage startups defining their first identity to established businesses evolving for new markets, we bring the same level of care, craft, and creative ambition to every brief.'),
('owner_name', 'Andiswa Phewa'),
('owner_title', 'Creative Director'),
('stat1_value', '50+'),
('stat1_label', 'Projects Completed'),
('stat2_value', '40+'),
('stat2_label', 'Happy Clients'),
('stat3_value', '5+'),
('stat3_label', 'Years Experience'),
('stat4_value', '98%'),
('stat4_label', 'Client Satisfaction'),
('instagram_url', ''),
('linkedin_url', ''),
('twitter_url', '');
