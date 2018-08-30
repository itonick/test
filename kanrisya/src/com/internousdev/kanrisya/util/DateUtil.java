package com.internousdev.kanrisya.util;

import java.util.Date;
import java.text.SimpleDateFormat;

public class DateUtil {
	public String getDate(){
		Date date=new Date();
		SimpleDateFormat simpleDateFormat=new SimpleDateFormat("yyyy/MM/dd HH:mm:ss");
		return simpleDateFormat.format(date);
	}

}
