CREATE TABLE tbl_typ (
  typPK INT PRIMARY KEY,
  bezeichnung VARCHAR(200)
);

CREATE TABLE tbl_karte (
  kartePK VARCHAR(8) PRIMARY KEY,
  typFK INT,
  interpret VARCHAR(200),
  titel VARCHAR(255),
  linksuffix varchar(255),
  FOREIGN KEY(typFK) REFERENCES tbl_typ(typPK)
);

INSERT INTO tbl_typ VALUES
(1, 'Hörspiel'),
(2, 'Album'),
(3, 'Playlist'),
(4, 'Radio');

INSERT INTO tbl_karte (kartePK, typFK, interpret, titel, linksuffix) VALUES
('AABBCCDD',	1,	'Die Drei Fragezeichen',	'Folge 001: und der Super-Papagei',	'spotify/now/spotify:track:4N9tvSjWfZXx3eHKblYEWQ');