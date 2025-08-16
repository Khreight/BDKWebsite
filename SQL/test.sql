        SELECT
        u.userId AS id,
        u.firstName, u.lastName, u.birthday, u.email, u.phone,
        u.city, u.nationality, u.role, u.dateRequestMember, u.created_at,
        ci.name  AS city_name,
        co.name  AS country_name,
        co.flag  AS country_flag,
        r.nameRole AS role_name
        FROM user u
        LEFT JOIN city    ci ON ci.cityId    = u.city
        LEFT JOIN country co ON co.countryId = u.nationality
        LEFT JOIN role    r  ON r.roleId     = u.role
        WHERE u.password IS NOT NULL AND u.password != ''