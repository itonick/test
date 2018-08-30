package com.internousdev.kanrisya.dao;
import java.sql.SQLException;
import java.sql.Connection;
import java.sql.PreparedStatement;
import com.internousdev.kanrisya.util.DBConnector;
import com.internousdev.kanrisya.util.DateUtil;

public class BuyItemCompleteDAO {
	private DBConnector dbConnector=new DBConnector();
	private DateUtil dateUtil=new DateUtil();
	private Connection connection=dbConnector.getConnection();
	private String sql="INSERT INTO user_buy_item_transaction(item_transaction_id,total_price,total_count,user_master_id,pay,insert_date) VALUE(?,?,?,?,?,?)";
	public void buyItemInfo(String item_transaction_id,String user_master_id,String total_price,String total_count,String pay)throws SQLException{
		try{
			PreparedStatement preparedStatement=connection.prepareStatement(sql);
			preparedStatement.setString(1, item_transaction_id);
			preparedStatement.setString(2, total_price);
			preparedStatement.setString(3, total_count);
			preparedStatement.setString(4, user_master_id);
			preparedStatement.setString(5, pay);
			preparedStatement.setString(6, dateUtil.getDate());
			preparedStatement.execute();
		}catch(Exception e){
			e.printStackTrace();
		}finally{
			connection.close();
		}
	}

}
