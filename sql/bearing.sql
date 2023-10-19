-- Calculate bearing

DELIMITER $$
DROP FUNCTION IF EXISTS wp_bearing$$
DROP FUNCTION IF EXISTS wp_bevier_ctrlpts$$

CREATE FUNCTION `wp_bearing` (p1 POINT, p2 POINT) RETURNS FLOAT
NO SQL DETERMINISTIC
BEGIN
	DECLARE lat1 FLOAT;
	DECLARE lng1 FLOAT;
	DECLARE lat2 FLOAT;
	DECLARE lng2 FLOAT;
	DECLARE geom1 GEOMETRY;
	DECLARE geom2 GEOMETRY;
	DECLARE dLng FLOAT;
	DECLARE y FLOAT;
	DECLARE x FLOAT;
	DECLARE bearing FLOAT;

	SET geom1 = wp_first_geom( p1 ); 
	SET geom2 = wp_first_geom( p2 ); 
	SET lat1 = RADIANS( ST_Y( geom1 ));
	SET lng1 = RADIANS( ST_X( geom1 ));
	SET lat2 = RADIANS( ST_Y( geom2 ));
	SET lng2 = RADIANS( ST_X( geom2 ));

	SET dLng = lng2 - lng1 ;

	SET y = SIN( dLng ) * COS( lat2 );
	SET x = ( COS( lat1 ) * SIN ( lat2 ) ) - ( SIN( lat1 ) * COS(  lat2 ) * COS( dLng ) );
	SET bearing = DEGREES( ATAN2( y, x ) );

	RETURN bearing;
END$$


CREATE FUNCTION `wp_bevier_ctrlpts` (p1 POINT, p2 POINT, p3 POINT, factor FLOAT) RETURNS MULTIPOINT
NO SQL DETERMINISTIC
BEGIN
	DECLARE len_s FLOAT;
	DECLARE len_t FLOAT;
	DECLARE bearing_s FLOAT;
	DECLARE bearing_t FLOAT;
	DECLARE dbearing FLOAT;
	DECLARE bearing_m FLOAT;
	DECLARE ctrlpt1_text TEXT;
	DECLARE ctrlpt2_text TEXT;
	DECLARE ctrlpt1 GEOMETRY;
	DECLARE ctrlpt2 GEOMETRY;

	SET len_s = wp_distance_point_real(p2 , p1, 1);
	SET len_t = wp_distance_point_real(p2 , p3, 1);
	SET bearing_s = (wp_bearing(p2, p1) + 180 + 360 ) % 360;
	SET bearing_t = (wp_bearing(p2, p3) + 360 ) % 360;
	SET dbearing = bearing_t - bearing_s ;

	SET bearing_m = bearing_s + dbearing * len_s / (len_s + len_t);
	SET ctrlpt1_text = wp_point_bearing_distance_coord_pair( p2, bearing_m, - len_s / factor, 1 );
	SET ctrlpt2_text = wp_point_bearing_distance_coord_pair( p2, bearing_m, len_t / factor ,1 );
	SET ctrlpt1 = ST_GeomFromText( CONCAT('POINT(',ctrlpt1_text,')'));
	SET ctrlpt2 = ST_GeomFromText( CONCAT('POINT(',ctrlpt2_text,')'));

	RETURN MULTIPOINT( ctrlpt1 , ctrlpt2 );
END$$
 
DELIMITER ;
