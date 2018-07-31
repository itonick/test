package jp.co.internous.action;

public class Test2 {
	public static void main(String[] args){
		
		Person saburo=new Person("saburo");
		System.out.println(saburo.name);
		System.out.println(saburo.age);
		
		Person p=new Person(25);
		System.out.println(p.name);
		System.out.println(p.age);
		
		Person hanako=new Person("hanako",17);
		System.out.println(hanako.name);
		System.out.println(hanako.age);
	}

}
