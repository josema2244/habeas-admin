-- Módulo Admin: áreas, cédula de usuarios y responsable/área en trámites.
CREATE TABLE IF NOT EXISTS areas (
  id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL UNIQUE
);

INSERT INTO areas (nombre) VALUES
  ('Tecnología'), ('Legal'), ('Recursos Humanos'), ('Finanzas')
ON CONFLICT (nombre) DO NOTHING;

ALTER TABLE users ADD COLUMN IF NOT EXISTS area_id INT REFERENCES areas(id);
ALTER TABLE users ADD COLUMN IF NOT EXISTS cedula VARCHAR(10) UNIQUE;
ALTER TABLE processing_activities ADD COLUMN IF NOT EXISTS area_id INT REFERENCES areas(id);
ALTER TABLE processing_activities ADD COLUMN IF NOT EXISTS responsable_id INT REFERENCES users(id);

-- Datos de demostración (cédulas de prueba con dígito verificador válido)
UPDATE users SET cedula = '1712345675', area_id = (SELECT id FROM areas WHERE nombre = 'Tecnología')       WHERE correo = 'admin@habeas.com';
UPDATE users SET cedula = '1712345683', area_id = (SELECT id FROM areas WHERE nombre = 'Legal')            WHERE correo = 'oficial@habeas.com';
UPDATE users SET cedula = '1712345691', area_id = (SELECT id FROM areas WHERE nombre = 'Recursos Humanos') WHERE correo = 'responsable@habeas.com';

UPDATE processing_activities
SET area_id = (SELECT id FROM areas WHERE nombre = 'Recursos Humanos'),
    responsable_id = (SELECT id FROM users WHERE correo = 'responsable@habeas.com')
WHERE area_id IS NULL;