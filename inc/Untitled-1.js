SELECT CONCAT_WS(' ', i1.term, i2.term, i3.term, i4.term ) AS c_term, 
	i1.`object_id` AS c_object_id, 
	i1.title + i2.title + i3.title + i4.title AS c_title,
	i1.content + i2.content + i3.content + i4.content AS c_content,
	 8 * ( i1.title + i2.title + i3.title + i4.title ) + 5 * ( i1.content + i2.content + i3.content + i4.content ) + 8 * ( i1.excerpt + i2.excerpt + i3.excerpt + i4.excerpt ) + 8 * ( i1.category + i2.category + i3.category + i4.category ) + 8 * ( i1.tag + i2.tag + i3.tag + i4.tag ) + 1 * ( i1.custom_field + i2.custom_field + i3.custom_field + i4.custom_field ) AS c_weight 
FROM wp_ps_index AS i1 
LEFT JOIN wp_ps_index as i2 ON i1.object_id = i2.object_id 
LEFT JOIN wp_ps_index as i3 ON i2.object_id = i3.object_id 
LEFT JOIN wp_ps_index as i4 ON i3.object_id = i4.object_id 
WHERE (( i1.`term` = 'lack' OR i1.`term_reverse` LIKE CONCAT(REVERSE('lack'), '%') ) 
	AND ( i2.`term` = 'day' OR i2.`term_reverse` LIKE CONCAT(REVERSE('day'), '%') ) 
	AND ( i3.`term` = 'ber' OR i3.`term_reverse` LIKE CONCAT(REVERSE('ber'), '%') ) 
	AND ( i4.`term` = 'nday' OR i4.`term_reverse` LIKE CONCAT(REVERSE('nday'), '%') )) 
	AND i1.object_type IN ( 'post_post', 'post_page' ) 
GROUP BY i1.object_id 
ORDER BY 
	(
		CASE 
			WHEN i1.term LIKE 'lack' THEN 1
			WHEN i2.term LIKE 'day' THEN 1
			WHEN i3.term LIKE 'ber' THEN 1
			WHEN i4.term LIKE 'nday' THEN 1

			WHEN i1.term_reverse LIKE 'kcal%' THEN 2
			WHEN i2.term_reverse LIKE 'yad%' THEN 2
			WHEN i3.term_reverse LIKE 'reb%' THEN 2
			WHEN i4.term_reverse LIKE 'yadn%' THEN 2
		END
	),
c_weight DESC