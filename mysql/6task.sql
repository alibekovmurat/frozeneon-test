SELECT
    date_format(analytics.time_created,'%Y-%m-%d %H:00') AS period,
    boost.id as boosterpack_id,
    SUM(amount) AS amount,
    SUM(items.price) as received
FROM analytics
         INNER JOIN boosterpack_info bi ON analytics.object_id = bi.id
         INNER JOIN boosterpack boost ON bi.boosterpack_id = boost.id
         INNER JOIN items ON bi.item_id = items.id
WHERE analytics.time_created > DATE_SUB(NOW(), INTERVAL 30 DAY) AND object = 'boosterpack'
GROUP BY date_format(analytics.time_created,'%Y%m%d%h'), period, boost.id;

SELECT user.id, user.personaname, user.wallet_total_refilled, user.wallet_balance, user.likes_balance, a.total_likes_received
FROM `user` INNER JOIN (SELECT  user_id, SUM(items.price) as total_likes_received  FROM `analytics` INNER JOIN boosterpack_info bi
ON bi.id = analytics.object_id INNER JOIN items ON bi.item_id = items.id GROUP BY user_id) a on user.id = a.user_id;