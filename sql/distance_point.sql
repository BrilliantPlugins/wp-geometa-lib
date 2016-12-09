-- Determine the didstance between two points in 4326, in meters or miles

DELIMITER $$

DROP FUNCTION IF EXISTS wp_distance_point_m$$
DROP FUNCTION IF EXISTS wp_distance_point_mi$$
DROP FUNCTION IF EXISTS wp_distance_point_real$$

CREATE FUNCTION wp_distance_point_m(p1 POINT, p2 POINT) RETURNS FLOAT
NO SQL DETERMINISTIC
BEGIN
	RETURN wp_distance_point_real(p1, p2, 6371000); -- Earth's radius in meters
END$$

CREATE FUNCTION wp_distance_point_mi(p1 POINT, p2 POINT) RETURNS FLOAT 
NO SQL DETERMINISTIC
BEGIN
	RETURN wp_distance_point_real(p1, p2, 3959); -- Earth's radius in miles
END$$

CREATE FUNCTION wp_distance_point_real(p1 POINT, p2 POINT, radius FLOAT) RETURNS FLOAT
NO SQL DETERMINISTIC
BEGIN


	DECLARE lat1 FLOAT;
	DECLARE lat2 FLOAT;
	DECLARE deltalon FLOAT;
	DECLARE geom1 GEOMETRY;
	DECLARE geom2 GEOMETRY;

	-- http://www.movable-type.co.uk/scripts/latlong.html
	-- var φ1 = lat1.toRadians()
	-- var φ2 = lat2.toRadians()
	-- Δλ = (lon2-lon1).toRadians()
	-- R = 6371e3; // gives d in metres
	-- var d = Math.acos( Math.sin(φ1)*Math.sin(φ2) + Math.cos(φ1)*Math.cos(φ2) * Math.cos(Δλ) ) * R;

	SET geom1 = wp_first_geom( p1 );
	SET geom2 = wp_first_geom( p2 );

	SET lat1 = RADIANS( Y( geom1 ) );
	SET lat2 = RADIANS( Y( geom2 ) );
	SET deltalon = RADIANS( X( geom2 ) - X( geom1 ) );

	RETURN ACOS( SIN( lat1 ) * SIN( lat2 ) + COS( lat1 ) * COS( lat2 ) * COS( deltalon ) ) * radius; 
END$$

DELIMITER ;
