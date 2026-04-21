CREATE TABLE `crud_imagenes_thumbnails` (
  `idLista` int NOT NULL AUTO_INCREMENT,
  `id_img` int NOT NULL,
  `origen` varchar(100) DEFAULT NULL,
  `etiqueta` varchar(20) NOT NULL,
  `picture` varchar(60) DEFAULT NULL,
  `url_imagen` varchar(255) NOT NULL,
  `url_picture` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `id_proyecto` int DEFAULT '9967',
  `q_descargas` int DEFAULT NULL,
  PRIMARY KEY (`idLista`),
  KEY `idx_thumb_etiqueta` (`etiqueta`),
  KEY `idx_thumb_id_img` (`id_img`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb3;