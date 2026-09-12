CREATE DATABASE IF NOT EXISTS chorombo
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE chorombo;

DROP TABLE IF EXISTS documentos;
DROP TABLE IF EXISTS tipos_documento;

CREATE TABLE tipos_documento (
    id     INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_tipos_documento_nombre (nombre)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

INSERT INTO tipos_documento (nombre) VALUES
    ('Memo'),
    ('Oficio'),
    ('Citación de apoderados'),
    ('Acuerdo de apoderados'),
    ('Reunión comunal'),
    ('Permiso administrativo');

CREATE TABLE documentos (
    id                      INT UNSIGNED NOT NULL AUTO_INCREMENT,
    titulo                  VARCHAR(255) NOT NULL,
    tipo_documento_id       INT UNSIGNED NOT NULL,
    fecha                   DATE NOT NULL,
    descripcion             TEXT NULL,
    archivo                 VARCHAR(255) NULL,
    archivo_nombre_original VARCHAR(255) NULL,
    created_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_documentos_tipo_documento (tipo_documento_id),
    KEY idx_documentos_fecha (fecha),
    CONSTRAINT fk_documentos_tipo_documento
        FOREIGN KEY (tipo_documento_id) REFERENCES tipos_documento (id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;
