package com.internousdev.ecsite.dao;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.ArrayList;
import com.internousdev.ecsite.dto.UserListDTO;
import com.internousdev.ecsite.util.DBConnector;

public class UserListDAO {
	private DBConnector dbConnector=new DBConnector();
	private Connection connection=dbConnector.getConnection();

	public ArrayList<UserListDTO> getUserInfo() throws SQLException{
		ArrayList<UserListDTO> userListDTO=new ArrayList<UserListDTO>();
		String sql=
				"SELECT id,login_id,login_pass,user_name,insert_date FROM login_user_transaction ORDER BY insert_date DESC";
		try{
			PreparedStatement preparedStatement=connection.prepareStatement(sql);

			ResultSet resultSet=preparedStatement.executeQuery();

			while(resultSet.next()){
				UserListDTO dto=new UserListDTO();
				dto.setId(resultSet.getInt("id"));
				dto.setLoginId(resultSet.getString("login_id"));
				dto.setLoginPass(resultSet.getString("login_pass"));
				dto.setUserName(resultSet.getString("user_name"));
				dto.setInsert_date(resultSet.getString("insert_date"));
				userListDTO.add(dto);
			}
		}catch(Exception e){
			e.printStackTrace();
		}finally {
			connection.close();
		}
		return userListDTO;
	}

	public int userHistoryDelete(String login_id,String login_pass,String user_name) throws SQLException{
		String sql="DELETE FROM login_user_transaction";

		PreparedStatement preparedStatement;
		int result=0;
		try{
			preparedStatement=connection.prepareStatement(sql);
			preparedStatement.setString(1,login_id);
			preparedStatement.setString(2,login_pass);
			preparedStatement.setString(3,user_name);
			result=preparedStatement.executeUpdate();
		}catch(SQLException e){
			e.printStackTrace();
		}finally {
			connection.close();
		}
		return result;
	}


}
