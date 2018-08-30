package com.internousdev.kanrisya.dao;
import java.sql.Connection;
import java.sql.SQLException;
import java.sql.PreparedStatement;
import com.internousdev.kanrisya.util.DBConnector;
import com.internousdev.kanrisya.util.DateUtil;

public class UserCreateCompleteDAO {
	private DBConnector dbConnector=new DBConnector();
	private DateUtil dateUtil=new DateUtil();
	private Connection connection=dbConnector.getConnection();
	private String sql="INSERT INTO login_user_transaction(login_id,login_password,user_name,insert_date) VALUES(?,?,?,?)";
	public void createUser(String loginUserId,String loginUserPassword,String userName) throws SQLException{
		try{
			PreparedStatement preparedStatement=connection.prepareStatement(sql);
			preparedStatement.setString(1, loginUserId);
			preparedStatement.setString(2, loginUserPassword);
			preparedStatement.setString(3, userName);
			preparedStatement.setString(4, dateUtil.getDate());
			preparedStatement.execute();
	}catch(Exception e){
		e.printStackTrace();
	}finally{
		connection.close();
	}
	}
}
