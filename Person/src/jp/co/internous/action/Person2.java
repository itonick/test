package jp.co.internous.action;

public class Person2 {
	public String name=null;
	public int age=0;

	public Person2(){}

	public Person2(String name,int age){
		this.name=name;
		this.age=age;
	}

	public Person2(String name){
		this.name=name;
		this.age=0;
	}

	public Person2(int age){
		this.name="名前無し";
		this.age=age;
	}

	public Person2(int age,String name){
		this.name=name;
		this.age=age;
	}

	public void talk(){
		System.out.println(this.name +"が話す");
		}
	public void walk(){
		System.out.println(this.name +"が歩く");
		}
	public void run(){
		System.out.println(this.name +"が走る");
		}

}
